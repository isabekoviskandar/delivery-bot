@extends('layouts.main')

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h1 class="text-center mb-4">Orders List</h1>
                <table class="table table-bordered table-hover text-center">
                    <thead>
                        <tr>
                            <th>ID</th>
                            {{-- <th>Username</th> --}}
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr id="orders-table">
                                <td>{{ $order->id }}</td>
                                {{-- <td>{{ $order->user->name }}</td> --}}
                                <td>{{ $order->time }}</td>
                                <td>{{ $order->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="text-center">
                    <button class="btn btn-custom" onclick="window.location.reload();">Refresh Orders</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
@endsection