<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Project;
use App\Policies\AppointmentPolicy;
use App\Policies\LeadPolicy;
use App\Policies\ListingPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Listing::class, ListingPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
    }
}
