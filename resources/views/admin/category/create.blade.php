@extends('layouts.main')

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2>Create New Category</h2>
                <form action="{{ route('category.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" name="name" class="form-control" id="name" required>
                    </div>

                    <div class="form-group">
                        <label for="is_active">Is Active?</label>
                        <select name="is_active" class="form-control" id="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success mt-3">Create Category</button>
                </form>
            </div>
        </div>
    </div>
@endsection
