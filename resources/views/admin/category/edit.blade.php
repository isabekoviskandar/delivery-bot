@extends('layouts.main')

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2>Edit Category</h2>
                <form action="{{ route('category.update', $category->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $category->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="is_active">Is Active?</label>
                        <select name="is_active" class="form-control" id="is_active">
                            <option value="1" {{ $category->is_active ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$category->is_active ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Update Category</button>
                </form>
            </div>
        </div>
    </div>
@endsection
