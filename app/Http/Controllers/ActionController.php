<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class ActionController extends Controller
{

    public $orders;
    public function index()
    {
        $orders = Order::all();
        return view('admin.action.index', compact('orders'));
    }
}
