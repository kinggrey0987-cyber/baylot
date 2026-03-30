<x-guest-layout>
    <div class="text-center mb-4">
        <h1 class="h4 mb-1">Verify Your Email</h1>
        <p class="text-muted mb-0">Please verify your email before continuing.</p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="d-grid gap-2 mb-3">
        @csrf
        <button class="btn btn-primary">Resend Verification Email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="d-grid">
        @csrf
        <button class="btn btn-outline-secondary">Sign Out</button>
    </form>
</x-guest-layout>
