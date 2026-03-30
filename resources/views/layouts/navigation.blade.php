<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                @auth
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                    @if (auth()->user()->role === 'admin')
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.agents.index') }}">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.index') }}">Reports</a></li>
                    @elseif (auth()->user()->role === 'broker')
                        @php($brokerUnreadCount = auth()->user()->unreadNotifications()->count())
                        <li class="nav-item"><a class="nav-link" href="{{ route('broker.agents.index') }}">My Agents</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('broker.projects.index') }}">Projects</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('broker.shared-listings.index') }}">Shared Listings</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('broker.notifications.index') }}">
                                Notifications
                                @if($brokerUnreadCount > 0)
                                    <span class="badge text-bg-danger ms-1">{{ $brokerUnreadCount }}</span>
                                @endif
                            </a>
                        </li>
                    @elseif (auth()->user()->role === 'agent')
                        @php($unreadNotificationsCount = auth()->user()->unreadNotifications()->count())
                        <li class="nav-item"><a class="nav-link" href="{{ route('agent.listings.index') }}">My Listings</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('agent.leads.queue') }}">Lead Queue</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('agent.notifications.index') }}">
                                Notifications
                                @if($unreadNotificationsCount > 0)
                                    <span class="badge text-bg-danger ms-1">{{ $unreadNotificationsCount }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav ms-auto">
                @auth
                    <li class="nav-item"><a class="nav-link" href="{{ route('profile.edit') }}">{{ auth()->user()->name }}</a></li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm mt-1" type="submit">Logout</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Register</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
