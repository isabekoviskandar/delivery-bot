@extends('layouts.main')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <h2>Available Foods</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Name</th>
                        <th>Available</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($foods as $food)
                        <tr>
                            <td>{{ $food->id }}</td>
                            <td>{{ $food->category->name }}</td>
                            <td>{{ $food->name }}</td>
                            <td>{{ $food->count }}</td>
                            <td>
                                <form action="{{ route('delivery.addToSession', $food->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Add to cart</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="col-12 mt-4">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sessionFoodsModal">
                View Cart
            </button>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="sessionFoodsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cart Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if (!empty($sessionFoods))
                        <form action="{{ route('delivery.updateSessionAndSendToTelegram') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Select User</label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">Choose user...</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Delivery Address</label>
                                <input type="text" name="address" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="delivery_time" class="form-label">Delivery Time</label>
                                <input type="datetime-local" name="delivery_time" class="form-control" required>
                            </div>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Name</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sessionFoods as $key => $sessionFood)
                                        <tr>
                                            <td>{{ $sessionFood['category'] }}</td>
                                            <td>{{ $sessionFood['name'] }}</td>
                                            <td>
                                                <input type="number" 
                                                       name="sessionFoods[{{ $key }}][count]" 
                                                       class="form-control" 
                                                       value="{{ $sessionFood['count'] ?? 1 }}" 
                                                       min="1">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="submit" class="btn btn-primary">Create Order</button>
                        </form>
                    @else
                        <p>Your cart is empty.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection