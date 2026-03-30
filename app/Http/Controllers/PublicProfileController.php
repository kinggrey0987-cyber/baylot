<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;

class PublicProfileController extends Controller
{
    /**
     * Public broker profile page — /brokers/{broker}
     */
    public function broker(string $broker): View
    {
        $broker = User::where('id', $broker)
            ->where('role', 'broker')
            ->where('is_active', true)
            ->firstOrFail();

        $projects = Project::where('broker_id', $broker->id)
            ->withCount([
                'listings as total_shared_listings_count' => fn ($query) => $query->where('listing_type', 'shared'),
                'listings as active_shared_listings_count' => fn ($query) => $query
                    ->where('listing_type', 'shared')
                    ->where('status', 'available'),
            ])
            ->orderBy('name')
            ->get();

        $activeAgents = $broker->managedAgents()
            ->where('is_active', true)
            ->withCount([
                'ownedListings as active_exclusive_listings_count' => fn ($query) => $query
                    ->where('listing_type', 'exclusive')
                    ->where('status', 'available'),
            ])
            ->orderBy('name')
            ->get();

        $sharedListingsQuery = Listing::query()
            ->publiclyVisible()
            ->where('listing_type', 'shared')
            ->whereHas('project', fn ($q) => $q->where('broker_id', $broker->id))
            ->with(['images', 'project'])
            ->select('listings.*')
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng')
            ->latest('listings.created_at');

        $teamExclusiveListingsQuery = Listing::query()
            ->publiclyVisible()
            ->where('listing_type', 'exclusive')
            ->whereHas('owner', fn ($q) => $q
                ->where('role', 'agent')
                ->where('is_active', true)
                ->where('broker_id', $broker->id))
            ->with(['images', 'owner'])
            ->select('listings.*')
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng')
            ->latest('listings.created_at');

        $sharedListings = (clone $sharedListingsQuery)->paginate(9, ['*'], 'shared_page')->withQueryString();
        $teamExclusiveListings = (clone $teamExclusiveListingsQuery)->paginate(9, ['*'], 'exclusive_page')->withQueryString();

        $stats = [
            'projects_count' => $projects->count(),
            'active_agents_count' => $activeAgents->count(),
            'shared_listings_count' => (clone $sharedListingsQuery)->count(),
            'team_exclusive_listings_count' => (clone $teamExclusiveListingsQuery)->count(),
        ];

        return view('public.broker-show', [
            'broker'       => $broker,
            'projects'     => $projects,
            'activeAgents' => $activeAgents,
            'sharedListings' => $sharedListings,
            'teamExclusiveListings' => $teamExclusiveListings,
            'stats' => $stats,
        ]);
    }

    /**
     * Public agent profile page — /agents/{agent}
     */
    public function agent(string $agent): View
    {
        $agent = User::where('id', $agent)
            ->where('role', 'agent')
            ->where('is_active', true)
            ->with('broker')
            ->firstOrFail();

        $listingsQuery = Listing::query()
            ->publiclyVisible()
            ->where('listing_type', 'exclusive')
            ->where('owner_agent_id', $agent->id)
            ->with(['images'])
            ->select('listings.*')
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng')
            ->latest('listings.created_at');

        $listings = (clone $listingsQuery)->paginate(9)->withQueryString();

        $relatedProjects = $agent->sharedProjects()
            ->withCount([
                'listings as active_shared_listings_count' => fn ($query) => $query
                    ->where('listing_type', 'shared')
                    ->where('status', 'available'),
            ])
            ->orderBy('name')
            ->limit(6)
            ->get();

        $agent->loadCount([
            'ownedListings as active_exclusive_listings_count' => fn ($query) => $query
                ->where('listing_type', 'exclusive')
                ->where('status', 'available'),
        ]);

        return view('public.agent-show', [
            'agent'    => $agent,
            'listings' => $listings,
            'relatedProjects' => $relatedProjects,
            'listingsCount' => (clone $listingsQuery)->count(),
        ]);
    }
}
