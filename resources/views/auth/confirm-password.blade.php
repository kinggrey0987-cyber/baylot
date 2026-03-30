<x-guest-layout>
    <div class="text-center mb-4">
        <h1 class="h4 mb-1">Confirm Password</h1>
        <p class="text-muted mb-0">Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="row g-3">
        @csrf
        <div class="col-12">
            <label for="password" class="form-label">Password</label>
            <input id="password" class="form-control" type="password" name="password" autocomplete="current-password" required>
        </div>
        <div class="col-12 d-grid">
            <button class="btn btn-primary">Confirm</button>
        </div>
    </form>
</x-guest-layout>
