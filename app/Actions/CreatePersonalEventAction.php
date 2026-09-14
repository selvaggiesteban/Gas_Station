<?php

namespace App\Actions;

use Illuminate\Support\Facades\Auth;

class CreatePersonalEventAction
{
    public function execute(array $data)
    {
        $user = Auth::user();

        // Get the highest position for user's personal schedules
        $maxPosition = $user->schedules()
            ->where('is_official', false)
            ->max('position') ?? 0;

        return $user->schedules()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_official' => false,
            'position' => $maxPosition + 1,
        ]);
    }
}
