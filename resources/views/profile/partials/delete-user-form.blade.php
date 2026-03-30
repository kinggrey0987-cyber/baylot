<section>
    <header class="mb-3">
        <h2 class="h5 mb-1 text-danger">Delete Account</h2>
        <p class="text-muted mb-0">This action is permanent and cannot be undone.</p>
    </header>

    <form method="post" action="{{ route('profile.destroy') }}" class="row g-3 module-form" onsubmit="return confirm('Are you sure you want to permanently delete your account?');">
        @csrf
        @method('delete')

        <div class="col-12">
            <label for="delete_password" class="form-label">Confirm your password</label>
            <input id="delete_password" name="password" type="password" class="form-control" placeholder="Password" required>
        </div>

        <div class="col-12 d-grid">
            <button class="btn btn-danger">Delete Account</button>
        </div>
    </form>
</section>
