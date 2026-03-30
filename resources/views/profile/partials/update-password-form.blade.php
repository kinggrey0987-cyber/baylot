<section>
    <header class="mb-3">
        <h2 class="h5 mb-1">Update Password</h2>
        <p class="text-muted mb-0">Ensure your account uses a secure password.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="row g-3 module-form">
        @csrf
        @method('put')

        <div class="col-12">
            <label for="update_password_current_password" class="form-label">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" />
        </div>

        <div class="col-12">
            <label for="update_password_password" class="form-label">New Password</label>
            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" />
        </div>

        <div class="col-12">
            <label for="update_password_password_confirmation" class="form-label">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" />
        </div>

        <div class="col-12 d-flex align-items-center gap-2">
            <button class="btn btn-primary">Save</button>

            @if (session('status') === 'password-updated')
                <span class="small text-muted">Saved.</span>
            @endif
        </div>
    </form>
</section>
