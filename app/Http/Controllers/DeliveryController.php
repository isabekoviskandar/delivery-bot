<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\User;
use App\Models\Order;
use App\Models\FoodOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
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
    
            // Validate request
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'address' => 'required|string',
                'delivery_time' => 'required|date',
                'sessionFoods' => 'required|array'
            ]);
    
            // Create order
            $order = Order::create([
                'user_id' => $request->user_id,
                'address' => $request->address,
                'time' => $request->delivery_time,
            ]);
    
            // Create food orders and update stock
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
                
            // Send order details message
            $message = "🆕 New Order #" . $order->id . "\n\n";
            $message .= "📍 Delivery Address: " . $request->address . "\n";
            $message .= "🕒 Delivery Time: " . $request->delivery_time . "\n\n";
            $message .= "🍽 Order Details:\n";
            
            foreach ($request->sessionFoods as $foodId => $foodData) {
                $food = Food::find($foodId);
                $message .= "• {$food->name} x {$foodData['count']}\n";
            }
    
            // Send message to user
            $messageResponse = Http::post("https://api.telegram.org/bot7819911021:AAHlBWvTFr7ewStAdPa0JMPFhN1zaMzAuV8/sendMessage", [
                'chat_id' => $user->chat_id,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
    
            if (!$messageResponse->successful()) {
                throw new \Exception('Failed to send Telegram message');
            }
    
            // Send location to user
            $locationResponse = Http::post("https://api.telegram.org/bot7819911021:AAHlBWvTFr7ewStAdPa0JMPFhN1zaMzAuV8/sendLocation", [
                'chat_id' => $user->chat_id,
                'latitude' => 41.2995,  // Your restaurant's latitude
                'longitude' => 69.2401  // Your restaurant's longitude
            ]);
    
            if (!$locationResponse->successful()) {
                throw new \Exception('Failed to send location');
            }
    
            // Clear session
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
}