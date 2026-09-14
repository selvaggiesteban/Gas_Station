<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Content Manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
         return $user->hasAnyRole(['Admin', 'Content Manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admins can update any user. Users cannot update themselves through this policy.
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can impersonate the model.
     */
    public function impersonate(User $user, User $model): bool
    {
        // An admin can impersonate anyone except another admin
        if ($user->hasRole('Admin') && !$model->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can leave an impersonation.
     */
    public function leaveImpersonate(User $user): bool
    {
        // Any user who is currently impersonating can leave
        return true;
    }
}
