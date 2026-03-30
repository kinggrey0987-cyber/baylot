<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BayLot') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('layouts.navigation')

    <main class="container py-4">
        @if (session('success'))
            <div class="toast align-items-center text-bg-success border-0 show mb-3" role="alert">
                <div class="d-flex">
                    <div class="toast-body">{{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="footer-light py-4 mt-4 border-top">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="text-muted">&copy; {{ date('Y') }} BayLot. All rights reserved.</span>
            <div>
                <a href="{{ route('home') }}" class="text-decoration-none text-muted me-3">Home</a>
                <a href="{{ route('profile.edit') }}" class="text-decoration-none text-muted">Profile</a>
            </div>
        </div>
    </footer>
</body>
</html>
