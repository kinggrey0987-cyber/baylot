<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Listing $listing): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isBrokerOrAgent();
    }

    public function update(User $user, Listing $listing): bool
    {
        if ($user->isBroker()) {
            return $listing->listing_type === 'shared'
                && $listing->project?->broker_id === $user->id;
        }

        return $user->isAgent()
            && $listing->listing_type === 'exclusive'
            && $listing->owner_agent_id === $user->id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $this->update($user, $listing);
    }
}
