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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // This project uses Bootstrap; force Bootstrap pagination templates globally.
        Paginator::useBootstrapFive();

        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Listing::class, ListingPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
    }
}
