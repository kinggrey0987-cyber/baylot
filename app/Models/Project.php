<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'broker_id',
        'created_by_admin_id',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    public function agents()
    {
        return $this->belongsToMany(User::class, 'project_agents', 'project_id', 'agent_id')
            ->where('users.role', 'agent')
            ->withTimestamps();
    }

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    public function scopeOwnedByBroker($query, string $brokerId)
    {
        return $query->where('broker_id', $brokerId);
    }
}
