<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>403 - Forbidden</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="{{ asset('assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/kaiadmin.min.css') }}" />
    <style>
        body {
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Public Sans', sans-serif;
        }
        .error-wrapper {
            text-align: center;
            padding: 2rem;
        }
        .error-wrapper img {
            max-width: 400px;
            width: 100%;
            margin-bottom: 2rem;
        }
        .error-wrapper h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #1a2035;
            margin-bottom: 0.5rem;
        }
        .error-wrapper p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <img src="{{ asset('assets/img/undraw/undraw_not_found_60pq.svg') }}" alt="Forbidden" />
        <h1>403</h1>
        <p>Access Denied — you don't have permission to access this page.</p>
        <a href="{{ url('/') }}" class="btn btn-primary btn-round px-4">Back to Dashboard</a>
    </div>
</body>
</html>
