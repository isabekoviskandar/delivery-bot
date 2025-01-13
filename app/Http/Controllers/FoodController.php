<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function index()
    {
        $foods = Food::all();
        $categories = Category::all();
        return view('admin.food.index', compact('foods', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.food.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'name' => 'required',
            'price' => 'required|numeric',
            'count' => 'required|numeric',
        ]);

        Food::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'price' => $request->price,
            'count' => $request->count,
        ]);

        return redirect()->route('food.index')->with('success', 'Food created successfully.');
    }


    public function edit($id)
    {
        $food = Food::findOrFail($id);
        $categories = Category::all(); 
        return view('admin.food.edit', compact('food', 'categories'));
    }
    

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required',
            'name' => 'required',
            'price' => 'required|numeric',
            'count' => 'required|numeric',
        ]);
    
        $food = Food::findOrFail($id);
        $food->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'price' => $request->price,
            'count' => $request->count,
        ]);
    
        return redirect()->route('food.index')->with('success', 'Food updated successfully.');
    }
    

    public function destroy($id)
    {
        $food = Food::findOrFail($id);

        $food->delete();

        return redirect('/food  ');
    }
}
