<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\LeadClaim;
use App\Models\Listing;
use App\Models\LoginOtp;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'broker_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isBroker(): bool
    {
        return $this->role === 'broker';
    }

    public function isBrokerOrAgent(): bool
    {
        return $this->isBroker() || $this->isAgent();
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    public function managedAgents()
    {
        return $this->hasMany(User::class, 'broker_id')->where('role', 'agent');
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by_admin_id');
    }

    public function brokerProjects()
    {
        return $this->hasMany(Project::class, 'broker_id');
    }

    public function ownedListings()
    {
        return $this->hasMany(Listing::class, 'owner_agent_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'buyer_id');
    }

    public function claimedLeads()
    {
        return $this->hasMany(LeadClaim::class, 'agent_id');
    }

    public function assignedAppointments()
    {
        return $this->hasMany(Appointment::class, 'assigned_agent_id');
    }

    public function sharedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_agents', 'agent_id', 'project_id')->withTimestamps();
    }

    public function loginOtps()
    {
        return $this->hasMany(LoginOtp::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistListings()
    {
        return $this->belongsToMany(Listing::class, 'wishlists', 'user_id', 'listing_id');
    }
}
