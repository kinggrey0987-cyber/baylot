<x-guest-layout>
    <div class="text-center mb-4">
        <h1 class="h4 mb-1">Forgot Password</h1>
        <p class="text-muted mb-0">Enter your email and we will send you a reset link.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="row g-3">
        @csrf
        <div class="col-12">
            <label for="email" class="form-label">Email address</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
        </div>
        <div class="col-12 d-grid">
            <button class="btn btn-primary">Send Reset Link</button>
        </div>
        <div class="col-12 text-center small">
            <a href="{{ route('login') }}" class="text-decoration-none">Back to sign in</a>
        </div>
    </form>
</x-guest-layout>
