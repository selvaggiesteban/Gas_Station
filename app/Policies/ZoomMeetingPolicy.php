<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZoomMeeting;
use Illuminate\Auth\Access\HandlesAuthorization;

class ZoomMeetingPolicy
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
     * Determine whether the user can view the zoom meeting.
     */
    public function view(User $user, ZoomMeeting $zoomMeeting): bool
    {
        // Teachers can view any meeting
        if ($user->hasRole('Teacher')) {
            return true;
        }

        // Students can view if they have an active subscription
        return $user->hasRole('Student') && $user->hasActiveSubscription();
    }

    /**
     * Determine whether the user can create zoom meetings.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Teacher', 'Content Manager']);
    }

    /**
     * Determine whether the user can update the zoom meeting.
     */
    public function update(User $user, ZoomMeeting $zoomMeeting): bool
    {
        // Only the creator or an Admin can update
        return $user->id === $zoomMeeting->created_by || $user->hasRole('Content Manager');
    }

    /**
     * Determine whether the user can delete the zoom meeting.
     */
    public function delete(User $user, ZoomMeeting $zoomMeeting): bool
    {
        return $user->id === $zoomMeeting->created_by || $user->hasRole('Content Manager');
    }
}
