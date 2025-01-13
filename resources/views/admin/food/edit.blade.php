@extends('layouts.main')

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2>Edit Food</h2>
                <form action="{{ route('food.update', $food->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" class="form-control" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $food->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="name">Food Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $food->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="text" name="price" class="form-control" value="{{ old('price', $food->price) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="count">Count</label>
                        <input type="number" name="count" class="form-control" value="{{ old('count', $food->count) }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Update Food</button>
                </form>
            </div>
        </div>
    </div>
@endsection
