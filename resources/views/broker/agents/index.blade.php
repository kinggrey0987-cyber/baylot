<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Broker Team Management</h1>
            <p class="app-section-subtitle">Create and manage agent accounts under your brokerage.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card module-card">
                <div class="card-header">Create Agent Account</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('broker.agents.store') }}" class="row g-3 module-form">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="name">Full name</label>
                            <input id="name" class="form-control" name="name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="email">Email address</label>
                            <input id="email" class="form-control" type="email" name="email" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="password">Password</label>
                            <input id="password" class="form-control" type="password" name="password" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="password_confirmation">Confirm password</label>
                            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="col-12 form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Active account</label>
                        </div>
                        <div class="col-12 d-grid"><button class="btn btn-primary">Create Agent</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card module-card">
                <div class="card-header">Your Agents</div>
                <div class="table-responsive">
                    <table class="table module-table mb-0">
                        <thead><tr><th>Name</th><th>Email</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            @forelse($agents as $agent)
                                <tr>
                                    <td>{{ $agent->name }}</td>
                                    <td>{{ $agent->email }}</td>
                                    <td>{!! $agent->is_active ? '<span class="badge text-bg-success status-badge">active</span>' : '<span class="badge text-bg-danger status-badge">disabled</span>' !!}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('broker.agents.toggle', $agent) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-outline-secondary">Toggle</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="table-empty">No agents found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $agents->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
