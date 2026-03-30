<x-guest-layout>
    <div class="text-center mb-3">
        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">Back to Homepage</a>
    </div>

    <div class="text-center mb-4">
        <h1 class="h4 mb-1">Welcome Back</h1>
        <p class="text-muted mb-0">Sign in to continue to your BayLot account.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="row g-3">
        @csrf
        <div class="col-12">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="email">
        </div>

        <div class="col-12">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>

        <div class="col-12 form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>

        <div class="col-12 d-grid">
            <button class="btn btn-primary">Sign In</button>
        </div>

        <div class="col-12 d-flex justify-content-between align-items-center small">
            <a href="{{ route('password.request') }}" class="text-decoration-none">Forgot password?</a>
            <a href="{{ route('register') }}" class="text-decoration-none">Create account</a>
        </div>
    </form>
</x-guest-layout>
