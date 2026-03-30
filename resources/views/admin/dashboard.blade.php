<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Admin Dashboard</h1>
            <p class="app-section-subtitle">Overview of agents, brokers, buyers, and lead activity.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="stat-card h-100">
                <p class="label">Agents and Brokers</p>
                <p class="value">{{ $agentCount }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card h-100">
                <p class="label">Buyers</p>
                <p class="value">{{ $buyerCount }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card h-100">
                <p class="label">Recent Claims</p>
                <p class="value">{{ $recentClaims->count() }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
