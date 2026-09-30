<?php

namespace App\Policies;

use App\Models\ModelJob;
use App\Models\User;

class ModelJobPolicy
{
    /** The poster's own project page (the public face of a project is the apply page). */
    public function view(User $user, ModelJob $job): bool
    {
        return $user->id === $job->user_id;
    }

    /** Only the poster changes a project: its brief, budget, deadline, images and whether it takes applications. */
    public function update(User $user, ModelJob $job): bool
    {
        return $user->id === $job->user_id;
    }
}
