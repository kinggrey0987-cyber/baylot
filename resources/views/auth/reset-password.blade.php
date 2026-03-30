<x-guest-layout>
    <div class="text-center mb-4">
        <h1 class="h4 mb-1">Reset Password</h1>
        <p class="text-muted mb-0">Create a new secure password for your account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="row g-3">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="col-12">
            <label for="email" class="form-label">Email address</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" required>
        </div>
        <div class="col-12">
            <label for="password" class="form-label">New password</label>
            <input id="password" class="form-control" type="password" name="password" autocomplete="new-password" required>
        </div>
        <div class="col-12">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <div class="col-12 d-grid">
            <button class="btn btn-primary">Update Password</button>
        </div>
    </form>
</x-guest-layout>
