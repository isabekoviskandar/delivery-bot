@extends('layouts.main')

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2>Create New Food</h2>
                <form action="{{ route('food.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" class="form-control" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="name">Food Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="text" name="price" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="count">Count</label>
                        <input type="number" name="count" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-success mt-3">Create Food</button>
                </form>
            </div>
        </div>
    </div>
@endsection
