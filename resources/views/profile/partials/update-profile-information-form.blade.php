<section>
    <header class="mb-3">
        <h2 class="h5 mb-1">Profile Information</h2>
        <p class="text-muted mb-0">Update your account profile information and email address.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="row g-3 module-form">
        @csrf
        @method('patch')

        <div class="col-12">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
        </div>

        <div class="col-12">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="small mt-2 mb-0">
                    Your email address is unverified.
                    <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">Click here to re-send the verification email.</button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 small text-success mb-0">A new verification link has been sent to your email address.</p>
                @endif
            @endif
        </div>

        <div class="col-12 d-flex align-items-center gap-2">
            <button class="btn btn-primary">Save</button>

            @if (session('status') === 'profile-updated')
                <span class="small text-muted">Saved.</span>
            @endif
        </div>
    </form>
</section>
