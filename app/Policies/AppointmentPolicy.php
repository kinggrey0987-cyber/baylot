<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBuyer()) {
            return $appointment->lead->buyer_id === $user->id;
        }

        if ($user->isBroker()) {
            $listing = $appointment->lead->listing;

            if ($listing->listing_type === 'shared') {
                return $listing->project?->broker_id === $user->id;
            }

            return $appointment->assignedAgent?->broker_id === $user->id;
        }

        return $user->isAgent() && $appointment->assigned_agent_id === $user->id;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isBroker()) {
            $listing = $appointment->lead->listing;

            if ($listing->listing_type === 'shared') {
                return $listing->project?->broker_id === $user->id;
            }

            return $appointment->assignedAgent?->broker_id === $user->id;
        }

        return $user->isAgent() && $appointment->assigned_agent_id === $user->id;
    }

    public function cancelByBuyer(User $user, Appointment $appointment): bool
    {
        return $user->isBuyer()
            && $appointment->lead->buyer_id === $user->id
            && $appointment->status === 'pending';
    }
}
