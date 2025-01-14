<?php

namespace App\Http\Controllers;

use App\Events\OrderEvent;
use App\Mail\SendMessage;
use App\Models\Order;
use App\Models\Steps;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Env;

class BotController extends Controller
{
    private string $telegramApiUrl;
    private array $allowedSteps = ['name', 'email', 'password', 'confirmation_code', 'image'];

    public function __construct()
    {
        $this->telegramApiUrl = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN');
    }

    private function store(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::post($this->telegramApiUrl . '/sendMessage', $payload);

            if (!$response->successful()) {
                Log::error('Telegram API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send message to Telegram', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId
            ]);
        }
    }


    private function validateEmail(string $email): bool
    {
        return Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email'
        ])->passes();
    }

    private function handleRegistrationStep(Steps $steps, string $chatId, $text, $photo = null): void
    {
        switch ($steps->step) {
            case 'name':
                if (strlen($text) < 2 || strlen($text) > 50) {
                    $this->store($chatId, "Please enter a valid name (2-50 characters):");
                    return;
                }
                $steps->update([
                    'name' => strip_tags($text),
                    'step' => 'email'
                ]);
                $this->store($chatId, "Please enter your email address:");
                break;

            case 'email':
                if (!$this->validateEmail($text)) {
                    $this->store($chatId, "Please enter a valid and unique email address:");
                    return;
                }
                $steps->update([
                    'email' => $text,
                    'step' => 'password'
                ]);
                $this->store($chatId, "Please enter your password (minimum 8 characters):");
                break;

            case 'password':
                if (strlen($text) < 8) {
                    $this->store($chatId, "Password must be at least 8 characters long. Please try again:");
                    return;
                }
                $confirmationCode = Str::random(6);

                try {
                    Mail::to($steps->email)->send(new SendMessage($steps->name, $confirmationCode));
                    $steps->update([
                        'password' => bcrypt($text),
                        'step' => 'confirmation_code',
                        'confirmation_code' => $confirmationCode
                    ]);
                    $this->store($chatId, "A confirmation code has been sent to your email. Please enter it:");
                } catch (Exception $e) {
                    Log::error('Failed to send confirmation email', ['error' => $e->getMessage()]);
                    $this->store($chatId, "Failed to send confirmation code. Please try again later.");
                }
                break;

            case 'confirmation_code':
                if ($text !== $steps->confirmation_code) {
                    $this->store($chatId, "Incorrect confirmation code. Please try again:");
                    return;
                }
                $steps->update(['step' => 'image']);
                $this->store($chatId, "Please send a profile picture:");
                break;

            case 'image':
                $this->handleImageUpload($steps, $chatId, $photo);
                break;
        }
    }

    private function handleImageUpload(Steps $steps, string $chatId, $photo): void
    {
        if (!$photo) {
            $this->store($chatId, "Please send a valid image:");
            return;
        }

        try {
            $fileId = end($photo)['file_id'];
            $response = Http::get("{$this->telegramApiUrl}/getFile", ['file_id' => $fileId]);

            if (!$response->successful()) {
                throw new Exception('Failed to get file information from Telegram');
            }

            $filePath = $response->json('result.file_path');
            $imageContent = file_get_contents("https://api.telegram.org/file/bot" . env('TELEGRAM_BOT_TOKEN') . "/{$filePath}");

            if (!$imageContent) {
                throw new Exception('Failed to download image');
            }

            $imageName = uniqid() . '.jpg';
            Storage::disk('public')->put("uploads/{$imageName}", $imageContent);

            $user = User::create([
                'name' => $steps->name,
                'email' => $steps->email,
                'password' => $steps->password,
                'chat_id' => $chatId,
                'image' => "uploads/{$imageName}",
                'status' => false
            ]);

            $this->notifyAdmins($user);
            $this->store($chatId, "Registration complete! Please wait for admin approval.");
            $steps->delete();
        } catch (Exception $e) {
            Log::error('Image upload failed', ['error' => $e->getMessage()]);
            $this->store($chatId, "Failed to process image. Please try again:");
        }
    }

    private function notifyAdmins(User $newUser): void
    {

        $admins = User::where('role', 'admin')->get();
        $message = "New user registered:\nName: {$newUser->name}\nEmail: {$newUser->email}";

        foreach ($admins as $admin) {
            $this->store($admin->chat_id, $message, [
                'inline_keyboard' => [
                    [
                        ['text' => 'Accept✅', 'callback_data' => "accept_{$newUser->id}"],
                        ['text' => 'Reject❌', 'callback_data' => "reject_{$newUser->id}"]
                    ]
                ]
            ]);
        }
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackData = $callbackQuery['data'];
        $callbackChatId = $callbackQuery['message']['chat']['id'];

        if (str_starts_with($callbackData, 'accept_')) {
            $this->handleUserApproval(str_replace('accept_', '', $callbackData), $callbackChatId);
        } elseif (str_starts_with($callbackData, 'reject_')) {
            $this->handleUserRejection(str_replace('reject_', '', $callbackData), $callbackChatId);
        }
    }

    private function handleUserApproval(string $userId, string $adminChatId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->update(['status' => true]);
            $this->store($adminChatId, "User {$user->name} has been approved.");
            $this->store($user->chat_id, "Your account has been approved! You can now use the bot.");
        }
    }

    private function handleUserRejection(string $userId, string $adminChatId): void
    {
        $user = User::find($userId);
        if ($user) {
            $userName = $user->name;
            $userChatId = $user->chat_id;
            $user->delete();
            $this->store($adminChatId, "User {$userName} has been rejected and removed.");
            $this->store($userChatId, "Your account has been rejected.");
        }
    }

    public function bot(Request $request)
    {
        try {
            $data = $request->all();

            // if (isset($data['callback_query'])) {
            //     $this->handleCallbackQuery($data['callback_query']);
            //     return response()->json(['status' => 'success']);
            // }

            // if (!isset($data['message'])) {
            //     return response()->json(['status' => 'error', 'message' => 'Invalid request']);
            // }

            $chatId = $data['message']['chat']['id'] ?? null;
            $text = $data['message']['text'] ?? null;
            $photo = $data['message']['photo'] ?? null;

            $steps = Steps::where('chat_id', $chatId)->first();
            $user = User::where('chat_id', $chatId)->first();

            if (isset($data['callback_query'])) {
                $callbackData = $data['callback_query']['data'];
                $chatId = $data['callback_query']['message']['chat']['id'];

                Log::info('Callback Data: ' . $callbackData);

                if (str_starts_with($callbackData, 'accept_order_')) {
                    $orderId = str_replace('accept_order_', '', $callbackData);
                    $this->updateOrderStatus($orderId, 'accepted', $chatId);
                } elseif (str_starts_with($callbackData, 'reject_order_')) {
                    $orderId = str_replace('reject_order_', '', $callbackData);
                    $this->updateOrderStatus($orderId, 'rejected', $chatId);
                }
            }
            if (!$steps && !$user) {
                $this->handleNewUser($chatId, $text);
            } elseif ($steps) {
                $this->handleRegistrationStep($steps, $chatId, $text, $photo);
            } else {
                $this->handleExistingUser($user, $chatId, $text);
            }

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error('Bot error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    private function handleNewUser(string $chatId, ?string $text): void
    {
        if ($text === '/start') {
            $this->store($chatId, "Hello! Welcome to the bot. Please choose to register:", [
                'keyboard' => [
                    [['text' => 'Register']],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]);
        } elseif ($text === 'Register') {
            Steps::create([
                'chat_id' => $chatId,
                'step' => 'name',
            ]);
            $this->store($chatId, "Please enter your username:");
        }
    }
    private function updateOrderStatus($orderId, $status, $chatId)
    {
        $order = Order::find($orderId);

        if ($order) {
            $order->update(['status' => $status]);
            event(new OrderEvent($order));

            $newKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => "Order {$status}", 'callback_data' => 'disabled'],
                    ],
                ],
            ];

            Http::post("https://api.telegram.org/bot7819911021:AAHlBWvTFr7ewStAdPa0JMPFhN1zaMzAuV8/editMessageReplyMarkup", [
                'chat_id' => $chatId,
                'message_id' => $order->telegram_message_id,
                'reply_markup' => json_encode($newKeyboard),
            ]);
        }
    }
    private function handleExistingUser(User $user, string $chatId, ?string $text): void
    {
        if (!$user->status) {
            $this->store($chatId, "Your account is pending approval by the admin.");
            return;
        }

        switch ($text) {
            case '/profile':
                $this->store($chatId, "Your Profile:\nName: {$user->name}\nEmail: {$user->email}");
                break;
            case '/users':
                if ($user->role === 'admin') {
                    $this->sendUserList($chatId);
                } else {
                    $this->store($chatId, "You are not authorized to view the user list.");
                }
                break;
        }
    }

    private function sendUserList(string $chatId): void
    {
        $users = User::all();
        $userList = $users->map(function ($user) {
            return "Name: {$user->name}, Email: {$user->email}, Status: " .
                ($user->status ? 'Active' : 'Pending');
        })->join("\n");

        $this->store($chatId, "List of all users:\n" . $userList);
    }
}
