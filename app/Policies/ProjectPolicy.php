<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isBrokerOrAgent();
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBroker()) {
            return $project->broker_id === $user->id;
        }

        if (! $user->isAgent()) {
            return false;
        }

        if (! is_null($project->broker_id)) {
            return $project->broker_id === $user->broker_id;
        }

        return $project->agents()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isBroker();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isBroker() && $project->broker_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isBroker() && $project->broker_id === $user->id;
    }
}
