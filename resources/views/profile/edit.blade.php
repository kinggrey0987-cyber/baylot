<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Profile Settings</h1>
            <p class="app-section-subtitle">Update your account information and security settings.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card module-card">
                <div class="card-header">Profile Information</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" class="row g-3 module-form">
                        @csrf
                        @method('PATCH')
                        <div class="col-12"><label class="form-label" for="name">Name</label><input id="name" class="form-control" name="name" value="{{ old('name', $user->name) }}" required></div>
                        <div class="col-12"><label class="form-label" for="email">Email</label><input id="email" class="form-control" name="email" type="email" value="{{ old('email', $user->email) }}" required></div>
                        <div class="col-12 d-grid"><button class="btn btn-primary">Save Profile</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card module-card mb-3">
                <div class="card-header">Change Password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}" class="row g-3 module-form">
                        @csrf
                        @method('PUT')
                        <div class="col-12"><label class="form-label" for="current_password">Current password</label><input id="current_password" type="password" class="form-control" name="current_password" required></div>
                        <div class="col-12"><label class="form-label" for="password">New password</label><input id="password" type="password" class="form-control" name="password" required></div>
                        <div class="col-12"><label class="form-label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required></div>
                        <div class="col-12 d-grid"><button class="btn btn-outline-primary">Update Password</button></div>
                    </form>
                </div>
            </div>

            <div class="card module-card border-danger">
                <div class="card-header text-danger">Delete Account</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Delete your account permanently?')" class="d-grid">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Delete Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
