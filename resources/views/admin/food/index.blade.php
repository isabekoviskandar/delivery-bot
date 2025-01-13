@extends('layouts.main')

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2>Food List</h2>
                <a href="{{ route('food.create') }}" class="btn btn-primary mb-3">Create Food</a>
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Category</th>
                            <th scope="col">Name</th>
                            <th scope="col">Price</th>
                            <th scope="col">Count</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($foods as $food)
                            <tr>
                                <td>{{ $food->id }}</td>
                                <td>{{ $food->category->name }}</td>
                                <td>{{ $food->name }}</td>
                                <td>{{ $food->price }}</td>
                                <td>{{ $food->count }}</td>
                                <td>
                                    <a href="{{ route('food.edit', $food->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="{{ route('food.destroy', $food->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
