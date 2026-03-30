<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLotRequest;
use App\Models\Listing;
use App\Models\Lot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class LotController extends Controller
{
    public function store(StoreLotRequest $request, Listing $listing): RedirectResponse
    {
        abort_if($listing->listing_type !== 'exclusive' || $listing->owner_agent_id !== auth()->id(), 403);

        DB::transaction(function () use ($request, $listing): void {
            $lot = Lot::create([
                'listing_id' => $listing->id,
                'lot_code' => $request->string('lot_code')->toString(),
                'area_sqm' => $request->input('area_sqm'),
                'status' => $request->string('status')->toString(),
            ]);

            DB::statement(
                'UPDATE lots SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?',
                [$request->string('geom_geojson')->toString(), $lot->id]
            );
        });

        return back()->with('success', 'Lot added.');
    }
}
