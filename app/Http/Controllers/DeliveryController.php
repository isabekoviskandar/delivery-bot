<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\User;
use App\Models\Order;
use App\Models\FoodOrder;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    // Define default coordinates as class constants
    private const DEFAULT_LATITUDE = 41.2995;
    private const DEFAULT_LONGITUDE = 69.2401;

    public function index(Request $request)
    {
        $foods = Food::all();
        $users = User::where('status', true)->get();
        $sessionFoods = $request->session()->get('foods', []);
        
        return view('admin.delivery.index', compact('foods', 'sessionFoods', 'users'));
    }

    public function addToSession(Request $request, $foodId)
    {
        $food = Food::findOrFail($foodId);
        $sessionFoods = $request->session()->get('foods', []);
        
        $sessionFoods[$food->id] = [
            'id' => $food->id,
            'name' => $food->name,
            'category' => $food->category->name,
            'count' => 1,
        ];
        
        $request->session()->put('foods', $sessionFoods);
        return redirect()->route('delivery.index')->with('success', 'Food added to cart!');
    }

    public function updateSessionAndSendToTelegram(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'user_id' => 'required|exists:users,id',
                'address' => 'required|string',
                'delivery_time' => 'required|date',
                'sessionFoods' => 'required|array'
            ]);

            $order = Order::create([
                'user_id' => $request->user_id,
                'address' => $request->address,
                'latitude' => self::DEFAULT_LATITUDE,
                'longitude' => self::DEFAULT_LONGITUDE,
                'time' => $request->delivery_time,
                'status' => 'pending',
            ]);

            foreach ($request->sessionFoods as $foodId => $foodData) {
                $food = Food::findOrFail($foodId);

                if ($food->count < $foodData['count']) {
                    throw new \Exception("Insufficient stock for {$food->name}");
                }

                FoodOrder::create([
                    'order_id' => $order->id,
                    'food_id' => $food->id,
                    'count' => $foodData['count'],
                ]);

                $food->update([
                    'count' => $food->count - $foodData['count']
                ]);
            }

            $user = User::find($request->user_id);

            Http::post("https://api.telegram.org/bot7819911021:AAHlBWvTFr7ewStAdPa0JMPFhN1zaMzAuV8/sendLocation", [
                'chat_id' => $user->chat_id,
                'latitude' => self::DEFAULT_LATITUDE,
                'longitude' => self::DEFAULT_LONGITUDE,
            ]);


            $message = "🆕 New Order #" . $order->id . "\n\n";
            $message .= "📍 Delivery Address: " . $request->address . "\n";
            $datetime = new DateTime($request->delivery_time);
            $time = $datetime->format('H:i');
            $message .= "🕒 Delivery Time: " . $time . "\n\n";
            
            $message .= "🍽 Order Details:\n";

            foreach ($request->sessionFoods as $foodId => $foodData) {
                $food = Food::find($foodId);
                $message .= "• {$food->name} x {$foodData['count']}\n";
            }

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Accept', 'callback_data' => 'accept_order_' . $order->id],
                        ['text' => '❌ Reject', 'callback_data' => 'reject_order_' . $order->id],
                    ],
                ],
            ];
            Log::info('Bordi mana akakakakakak');
            Http::post("https://api.telegram.org/bot7819911021:AAHlBWvTFr7ewStAdPa0JMPFhN1zaMzAuV8/sendMessage", [
                'chat_id' => $user->chat_id,
                'text' => $message,
                'reply_markup' => json_encode($keyboard),
                'parse_mode' => 'HTML',
            ]);

            $request->session()->forget('foods');

            DB::commit();

            return redirect()->route('delivery.index')->with('success', 'Order created and sent to user!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('delivery.index')
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    // public function handleTelegramWebhook(Request $request)
    // {
    //     Log::info('Webhook Data: ', $request->all());
        
    //     $data = $request->all();
        
    //     if (isset($data['callback_query'])) {
    //         $callbackData = $data['callback_query']['data'];
    //         $chatId = $data['callback_query']['message']['chat']['id'];
            
    //         Log::info('12');
    //         Log::info('Callback Data: ' . $callbackData);
    
    //         if (str_starts_with($callbackData, 'accept_order_')) {
    //             $orderId = str_replace('accept_order_', '', $callbackData);
    //             $this->updateOrderStatus($orderId, 'accepted', $chatId);
    //         } elseif (str_starts_with($callbackData, 'reject_order_')) {
    //             $orderId = str_replace('reject_order_', '', $callbackData);
    //             $this->updateOrderStatus($orderId, 'rejected', $chatId);
    //         }
    //     }
    // }

    // private function updateOrderStatus($orderId, $status, $chatId)
    // {
    //     $order = Order::find($orderId);
    
    //     if ($order) {
    //         $order->update(['status' => $status]);
    
    //         $newKeyboard = [
    //             'inline_keyboard' => [
    //                 [
    //                     ['text' => "Order {$status}", 'callback_data' => 'disabled'],
    //                 ],
    //             ],
    //         ];
    
    //         Http::post("https://api.telegram.org/bot7819911021:AAHlBWvTFr7ewStAdPa0JMPFhN1zaMzAuV8/editMessageReplyMarkup", [
    //             'chat_id' => $chatId,
    //             'message_id' => $order->telegram_message_id,
    //             'reply_markup' => json_encode($newKeyboard),
    //         ]);
    //     }
    // }
    
    
    


    
}