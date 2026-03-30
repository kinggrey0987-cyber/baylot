<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Broker Projects</h1>
            <p class="app-section-subtitle">Step 1: Create a project under your brokerage. Your active agents can handle site visits and lead claiming.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card module-card">
                <div class="card-header">Create Brokerage Project</div>
                <div class="card-body">
                    <p class="small text-muted mb-3">After saving a project, you will be redirected to Shared Listings to add your subdivision or housing inventory.</p>
                    <p class="small text-muted mb-3">Site visit coverage: your active agents ({{ $activeAgentCount }}).</p>
                    <form method="POST" action="{{ route('broker.projects.store') }}" class="row g-3 module-form">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="name">Project name</label>
                            <input id="name" class="form-control" name="name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-12 d-grid"><button class="btn btn-primary">Create Project</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card module-card">
                <div class="card-header">Projects</div>
                <div class="table-responsive">
                    <table class="table module-table mb-0">
                        <thead><tr><th>Name</th><th>Site Visit Coverage</th><th>Collective Listings</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($projects as $project)
                                <tr>
                                    <td>{{ $project->name }}</td>
                                    <td>
                                        Your active agents ({{ $activeAgentCount }})
                                    </td>
                                    <td>
                                        {{ $project->shared_listings_count }}
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('broker.shared-listings.index', ['project_id' => $project->id]) }}">Add Collective Listing</a>
                                            <button
                                                class="btn btn-sm btn-outline-secondary"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#projectEdit{{ $project->id }}"
                                                aria-expanded="false"
                                                aria-controls="projectEdit{{ $project->id }}"
                                            >
                                                Edit Project
                                            </button>
                                            <form method="POST" action="{{ route('broker.projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and its collective listings? Deletion is blocked if active site visits still exist.');" class="d-inline-block">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete Project</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="p-0 border-0">
                                        <div id="projectEdit{{ $project->id }}" class="collapse p-3 bg-light-subtle border-top">
                                            <form method="POST" action="{{ route('broker.projects.update', $project) }}" class="row g-2 module-form align-items-end">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label" for="project_name_{{ $project->id }}">Project name</label>
                                                    <input id="project_name_{{ $project->id }}" class="form-control" name="name" value="{{ $project->name }}" required>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label" for="project_description_{{ $project->id }}">Description</label>
                                                    <textarea id="project_description_{{ $project->id }}" class="form-control" name="description" rows="2">{{ $project->description }}</textarea>
                                                </div>
                                                <div class="col-12 col-md-2 d-grid">
                                                    <button class="btn btn-primary" type="submit">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="table-empty">No projects found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $projects->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
