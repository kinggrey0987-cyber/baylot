<x-guest-layout>
    <div class="text-center mb-3">
        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">Back to Homepage</a>
    </div>

    <div class="text-center mb-4">
        <h1 class="h4 mb-1">Create Account</h1>
        <p class="text-muted mb-0">Set up your BayLot account in a few steps.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="row g-3">
        @csrf
        <div class="col-12">
            <label for="name" class="form-label">Full name</label>
            <input id="name" class="form-control" name="name" value="{{ old('name') }}" autocomplete="name" required>
        </div>

        <div class="col-12">
            <label for="email" class="form-label">Email address</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
        </div>

        <div class="col-12">
            <label for="password" class="form-label">Password</label>
            <input id="password" class="form-control" type="password" name="password" autocomplete="new-password" required>
        </div>

        <div class="col-12">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <div class="col-12 d-grid">
            <button class="btn btn-primary">Create Account</button>
        </div>

        <div class="col-12 text-center small">
            Already registered?
            <a href="{{ route('login') }}" class="text-decoration-none">Sign in</a>
        </div>
    </form>
</x-guest-layout>
