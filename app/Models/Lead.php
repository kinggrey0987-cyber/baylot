<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'listing_id',
        'buyer_id',
        'message',
        'status',
        'inquiry_type',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function claim()
    {
        return $this->hasOne(LeadClaim::class);
    }

    public function appointment()
    {
        return $this->hasOne(Appointment::class);
    }

    public function messages()
    {
        return $this->hasMany(LeadMessage::class);
    }
}
