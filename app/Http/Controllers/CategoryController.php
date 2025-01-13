<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return view('admin.category.index' , compact('categories'));
    }


    public function create()
    {
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'is_active' => 'required|boolean',
        ]);

        Category::create([
            'name' => $request->name,
            'is_active' => $request->is_active,
        ]);

        return redirect('/category');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.category.edit' , compact('category'));
    }

    public function update(Request $request , $id)
    {
        $request->validate([
            'name' => 'required',
            'is_active' => 'required|boolean',
        ]);
        $category = Category::findOrFail($id);
        $category->update([
            'name' => $request->name,
            'is_active' => $request->is_active,
        ]);

        return redirect('/category');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        $category->delete();

        return redirect('/category  ');
    }

}
