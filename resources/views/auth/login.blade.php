<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #222;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }

        .login-container {
            background: #333;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #0ff;
        }

        .form-control {
            background: #444;
            color: #fff;
            border: none;
        }

        .form-control:focus {
            outline: none;
            box-shadow: 0 0 5px #0ff;
        }

        .btn-primary {
            background: #0ff;
            border: none;
            color: #000;
            font-weight: bold;
        }

        .btn-primary:hover {
            background: #0cc;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <form action="{{ route('auth.login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>
