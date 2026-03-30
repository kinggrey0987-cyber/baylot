<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'lead_id',
        'assigned_agent_id',
        'requested_start',
        'requested_end',
        'status',
        'agent_notes',
        'down_payment_received_at',
        'sale_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_start' => 'datetime',
            'requested_end' => 'datetime',
            'down_payment_received_at' => 'datetime',
            'sale_confirmed_at' => 'datetime',
        ];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }
}
