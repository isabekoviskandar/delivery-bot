<?php

namespace App\Http\Controllers;

use App\Mail\SendMessage;
use App\Models\Steps;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BotController extends Controller
{
    public function store(int $chatId, string $text, $replyMarkup = null)
    {
        $token = "https://api.telegram.org/bot7819911021:AAHlBWvTFr7ewStAdPa0JMPFhN1zaMzAuV8";
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        Http::post($token . '/sendMessage', $payload);
    }

    public function bot(Request $request)
    {
        Log::info('Webhook received', $request->all());
        try {
            $data = $request->all();
            $chat_id = $data['message']['chat']['id'];
            $text = $data['message']['text'] ?? null;
            $photo = $data['message']['photo'] ?? null;

            $Steps = Steps::where('chat_id', $chat_id)->first();
            Log::info($Steps);

            if (!$Steps) {
                if ($text === '/start') {
                    $this->store($chat_id, "Hello! Welcome to the bot please enter the system first:", [
                        'keyboard' => [
                            [
                                ['text' => 'Enter'],
                            ]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true,
                    ]);
                    return;
                }

                if ($text === 'Enter') {
                    $Steps = Steps::create([
                        'chat_id' => $chat_id,
                        'step' => 'name'
                    ]);
                    $this->store($chat_id, "Please enter your username:");
                    Log::info($Steps);
                    return;
                }
            } else {
                switch ($Steps->step) {
                    case 'name':
                        $Steps->name = $text;
                        $Steps->step = 'email';
                        $Steps->save();
                        $this->store($chat_id, "Please enter your email address:");
                        return;

                    case 'email':
                        $Steps->email = $text;
                        $Steps->step = 'password';
                        $Steps->save();
                        $this->store($chat_id, "Please enter your password:");
                        return;

                    case 'password':
                        $Steps->password = bcrypt($text);
                        $Steps->step = 'confirmation_code';
                        $Steps->save();

                        $confirmation_code = Str::random(6);
                        $email = $Steps->email;
                        $name = $Steps->name;

                        try {
                            Mail::to($email)->send(new SendMessage($name, $confirmation_code));
                            Log::info('Email sent successfully');
                            $this->store($chat_id, "A confirmation code has been sent to your email. Please enter it:");
                        } catch (\Exception $e) {
                            Log::error('Failed to send email: ' . $e->getMessage());
                            $this->store($chat_id, "An error occurred while sending the confirmation code. Please try again.");
                        }

                        $Steps->confirmation_code = $confirmation_code;
                        $Steps->save();
                        return;

                    case 'confirmation_code':
                        if ($text === $Steps->confirmation_code) {
                            $Steps->step = 'image';
                            $Steps->save();
                            $this->store($chat_id, "The confirmation code is correct. Please send a profile picture:");
                        } else {
                            $this->store($chat_id, "The confirmation code is incorrect. Please try again:");
                        }
                        return;

                    case 'image':
                        if ($photo) {
                            $file_id = end($photo)['file_id'];

                            $telegram_api = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN');
                            $file_path_response = file_get_contents("{$telegram_api}/getFile?file_id={$file_id}");
                            $response = json_decode($file_path_response, true);

                            if (isset($response['result']['file_path'])) {
                                $file_path = $response['result']['file_path'];
                                $download_url = "https://api.telegram.org/file/bot" . env('TELEGRAM_BOT_TOKEN') . "/{$file_path}";

                                $image_name = uniqid() . '.jpg';
                                $image_content = file_get_contents($download_url);

                                if ($image_content) {
                                    Storage::disk('public')->put("uploads/{$image_name}", $image_content);
                                    $image_path = "uploads/{$image_name}";

                                    $user = User::create([
                                        'name' => $Steps->name,
                                        'email' => $Steps->email,
                                        'password' => $Steps->password,
                                        'chat_id' => $chat_id,
                                        'image' => "uploads/{$image_name}",
                                    ]);

                                    $this->store($chat_id, "You have successfully registered!");

                                    $Steps->delete();
                                } else {
                                    $this->store($chat_id, "There was an issue downloading the image. Please try again:");
                                }
                            } else {
                                $this->store($chat_id, "There was an issue downloading the image. Please try again:");
                            }
                        } else {
                            $this->store($chat_id, "Please send a picture!");
                        }
                        return;
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage()
            ]);
        }
    }
}
