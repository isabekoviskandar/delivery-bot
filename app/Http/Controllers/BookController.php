<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::all();
        return response()->json($books);
    }

    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        return response()->json($book);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10048',
        ]);

        $imagePath = $request->file('image')->store('books', 'public');

        $book = Book::create([
            'name' => $validated['name'],
            'image' => $imagePath,
        ]);

        return response()->json(['message' => 'Book created successfully', 'book' => $book], 201);
    }

    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'image' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10048',
        ]);

        if ($request->has('name')) {
            $book->name = $validated['name'];
        }

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($book->image);

            $imagePath = $request->file('image')->store('books', 'public');
            $book->image = $imagePath;
        }

        $book->save();

        return response()->json(['message' => 'Book updated successfully', 'book' => $book]);
    }

    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        Storage::disk('public')->delete($book->image);

        $book->delete();

        return response()->json(['message' => 'Book deleted successfully']);
    }
}
