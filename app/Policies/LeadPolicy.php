<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBuyer()) {
            return $lead->buyer_id === $user->id;
        }

        $listing = $lead->listing;

        if ($user->isBroker()) {
            if ($listing->listing_type === 'exclusive') {
                return $listing->owner?->broker_id === $user->id;
            }

            return $listing->project?->broker_id === $user->id;
        }

        if (! $user->isAgent()) {
            return false;
        }

        if ($lead->claim && $lead->claim->agent_id === $user->id) {
            return true;
        }

        if ($listing->listing_type === 'exclusive') {
            return $listing->owner_agent_id === $user->id;
        }

        if (! is_null($listing->project?->broker_id)) {
            return $listing->project->broker_id === $user->broker_id;
        }

        return true;
    }

    public function claim(User $user, Lead $lead): bool
    {
        if (! $user->isAgent()) {
            return false;
        }

        if ($lead->status !== 'new' || $lead->claim) {
            return false;
        }

        $listing = $lead->listing;

        if ($listing->listing_type !== 'shared') {
            return false;
        }

        $project = $listing->project;

        if (! $project) {
            return false;
        }

        if (is_null($project->broker_id)) {
            return true;
        }

        if (is_null($user->broker_id)) {
            return false;
        }

        return $project->broker_id === $user->broker_id;
    }
}
