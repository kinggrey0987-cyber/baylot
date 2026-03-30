<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'listing_type',
        'property_type',
        'project_id',
        'owner_agent_id',
        'title',
        'description',
        'price',
        'price_max',
        'lot_area_sqm',
        'lot_area_max_sqm',
        'floor_area_sqm',
        'price_per_sqm',
        'bedrooms',
        'bedrooms_max',
        'bathrooms',
        'bathrooms_max',
        'floors',
        'floors_max',
        'available_units',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_max' => 'decimal:2',
            'lot_area_sqm' => 'decimal:2',
            'lot_area_max_sqm' => 'decimal:2',
            'floor_area_sqm' => 'decimal:2',
            'price_per_sqm' => 'decimal:2',
            'bedrooms' => 'integer',
            'bedrooms_max' => 'integer',
            'bathrooms' => 'integer',
            'bathrooms_max' => 'integer',
            'floors' => 'integer',
            'floors_max' => 'integer',
            'available_units' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_agent_id');
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class);
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function scopeSharedOwnedByBroker($query, string $brokerId)
    {
        return $query->where('listing_type', 'shared')
            ->whereHas('project', fn ($projectQuery) => $projectQuery->where('broker_id', $brokerId));
    }

    public function scopePubliclyActive($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeInsideNegrosOccidental($query)
    {
        return $query->whereExists(function ($subQuery): void {
            $subQuery->selectRaw('1')
                ->from('geofences')
                ->where('name', 'Negros Occidental')
                ->whereRaw('ST_Covers(geofences.geom, listings.location)');
        });
    }

    public function scopeAvailableUnitsForListing($query, Listing $listing)
    {
        $query->publiclyActive()->insideNegrosOccidental()->where('id', '!=', $listing->id);

        if ($listing->listing_type === 'shared' && ! is_null($listing->project_id)) {
            return $query
                ->where('listing_type', 'shared')
                ->where('project_id', $listing->project_id);
        }

        if ($listing->listing_type === 'exclusive' && ! is_null($listing->owner_agent_id)) {
            return $query
                ->where('listing_type', 'exclusive')
                ->where('owner_agent_id', $listing->owner_agent_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeOwnedByAgent($query, string $agentId)
    {
        return $query->where('listing_type', 'exclusive')
            ->where('owner_agent_id', $agentId);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->publiclyActive()
            ->insideNegrosOccidental()
            ->where(function ($subQuery): void {
                $subQuery
                    ->where(function ($sharedQuery): void {
                        $sharedQuery->where('listing_type', 'shared')
                            ->whereHas('project.broker', function ($brokerQuery): void {
                                $brokerQuery
                                    ->where('role', 'broker')
                                    ->where('is_active', true);
                            });
                    })
                    ->orWhere(function ($exclusiveQuery): void {
                        $exclusiveQuery->where('listing_type', 'exclusive')
                            ->whereHas('owner', function ($ownerQuery): void {
                                $ownerQuery
                                    ->where('role', 'agent')
                                    ->where('is_active', true);
                            });
                    });
            });
    }

    public function scopeFeaturedPubliclyVisible($query)
    {
        return $query->publiclyVisible()->featured();
    }

    public function scopeWithBounds($query, float $swLat, float $swLng, float $neLat, float $neLng)
    {
        return $query
            ->whereRaw('ST_Y(location) BETWEEN ? AND ?', [$swLat, $neLat])
            ->whereRaw('ST_X(location) BETWEEN ? AND ?', [$swLng, $neLng]);
    }
}
