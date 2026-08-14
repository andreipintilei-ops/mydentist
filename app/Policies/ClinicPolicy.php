<?php

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;

class ClinicPolicy
{
    /**
     * Edit a clinic: the assigned owner (successful claim) or any moderator/admin.
     * This is what the owner panel (/cont) and the admin dashboard both gate on.
     */
    public function update(User $user, Clinic $clinic): bool
    {
        return $user->canModerate() || $clinic->claimed_by === $user->id;
    }

    /**
     * Alias used by the owner panel for clarity at call sites.
     */
    public function manage(User $user, Clinic $clinic): bool
    {
        return $this->update($user, $clinic);
    }

    public function delete(User $user, Clinic $clinic): bool
    {
        return $user->canModerate();
    }

    /**
     * A clinic can be claimed only while it has no owner yet. Moderators handle
     * re-assignment through the admin dashboard instead.
     */
    public function claim(User $user, Clinic $clinic): bool
    {
        return $clinic->claimed_by === null;
    }
}
