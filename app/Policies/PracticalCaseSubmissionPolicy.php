<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PracticalCaseSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;

class PracticalCaseSubmissionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        return null;
    }

    public function view(User $user, PracticalCaseSubmission $submission): bool
    {
        // Student can view their own, Teachers can view all
        return $user->id === $submission->user_id || $user->hasAnyRole(['Teacher', 'Content Manager']);
    }

    public function review(User $user, PracticalCaseSubmission $submission): bool
    {
        // Only teachers or content managers can review
        return $user->hasAnyRole(['Teacher', 'Content Manager']);
    }
}
