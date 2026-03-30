<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'listing_id',
        'lot_code',
        'area_sqm',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'area_sqm' => 'decimal:2',
        ];
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
