<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->isBuyer(), 403);

        $wishlists = Wishlist::query()
            ->where('user_id', $buyer->id)
            ->with(['listing.images', 'listing.project', 'listing.owner'])
            ->latest('created_at')
            ->paginate(12);

        return view('buyer.wishlist.index', [
            'wishlists' => $wishlists,
        ]);
    }

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->isBuyer(), 403);

        Wishlist::firstOrCreate([
            'user_id' => $buyer->id,
            'listing_id' => $listing->id,
        ]);

        return back()->with('success', 'Listing added to your wishlist.');
    }

    public function destroy(Request $request, Listing $listing): RedirectResponse
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->isBuyer(), 403);

        Wishlist::query()
            ->where('user_id', $buyer->id)
            ->where('listing_id', $listing->id)
            ->delete();

        return back()->with('success', 'Listing removed from your wishlist.');
    }
}
