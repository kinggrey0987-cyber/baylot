<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Listing;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicListingController extends Controller
{
    public function index(Request $request): View
    {
        $featuredListings = $this->availableListingsQuery($request)
            ->latest()
            ->limit(6)
            ->get();

        $savedListingIds = $this->resolveSavedListingIds($request);

        return view('public.home', [
            'featuredListings' => $featuredListings,
            'savedListingIds' => $savedListingIds,
        ]);
    }

    public function properties(Request $request): View
    {
        $listings = $this->availableListingsQuery($request)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $savedListingIds = $this->resolveSavedListingIds($request);

        return view('public.properties', [
            'listings' => $listings,
            'savedListingIds' => $savedListingIds,
        ]);
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function show(Request $request, Listing $listing): View
    {
        abort_if(in_array($listing->status, ['sold', 'unavailable'], true), 404);

        if ($listing->status === 'reserved') {
            $user = auth()->user();
            $isStaff = in_array($user?->role, ['admin', 'agent', 'broker'], true);
            $isReservingBuyer = $user?->role === 'buyer'
                && Appointment::query()
                    ->whereHas('lead', function ($query) use ($listing, $user): void {
                        $query->where('listing_id', $listing->id)
                            ->where('buyer_id', $user->id);
                    })
                    ->whereIn('status', ['approved', 'completed'])
                    ->whereNotNull('down_payment_received_at')
                    ->exists();

            abort_unless($isStaff || $isReservingBuyer, 404);
        }

        $listing->load(['images', 'project.broker', 'owner.broker']);

        $spatial = DB::table('listings')
            ->where('id', $listing->id)
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng')
            ->first();

        $geofence = DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->selectRaw('ST_AsGeoJSON(geom) as geojson')
            ->value('geojson');

        $savedListingIds = $this->resolveSavedListingIds($request);

        $availableUnits = Listing::query()
            ->availableUnitsForListing($listing)
            ->with(['images', 'project.broker', 'owner.broker'])
            ->latest()
            ->limit(6)
            ->get();

        return view('public.listing-show', [
            'listing' => $listing,
            'lat' => $spatial?->lat,
            'lng' => $spatial?->lng,
            'geofenceGeoJson' => $geofence,
            'isSaved' => in_array($listing->id, $savedListingIds, true),
            'savedListingIds' => $savedListingIds,
            'availableUnits' => $availableUnits,
        ]);
    }

    public function startSiteVisitRequest(Listing $listing): RedirectResponse
    {
        abort_if($listing->status !== 'available', 404);

        return redirect()->to(route('listings.show', $listing).'#request-site-visit');
    }

    private function availableListingsQuery(Request $request): Builder
    {
        return Listing::query()
            ->publiclyActive()
            ->with(['images', 'project.broker', 'owner.broker'])
            ->select('listings.*')
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng')
            ->when(
                $request->filled('property_type'),
                fn (Builder $query) => $query->where('property_type', $request->string('property_type')->toString())
            )
            ->when($request->filled('min_price'), fn (Builder $query) => $query->where('price', '>=', $request->float('min_price')))
            ->when($request->filled('max_price'), fn (Builder $query) => $query->where('price', '<=', $request->float('max_price')))
            ->when($request->filled('keyword'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->toString().'%';

                $query->where(function (Builder $subQuery) use ($keyword): void {
                    $subQuery->where('title', 'ilike', $keyword)
                        ->orWhere('description', 'ilike', $keyword);
                });
            });
    }

    private function resolveSavedListingIds(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! $user->isBuyer()) {
            return [];
        }

        return Wishlist::query()
            ->where('user_id', $user->id)
            ->pluck('listing_id')
            ->all();
    }
}
