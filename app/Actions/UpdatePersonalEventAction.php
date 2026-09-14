<?php

namespace App\Actions;

use App\Models\Schedule;

class UpdatePersonalEventAction
{
    public function execute(Schedule $schedule, array $data): void
    {
        $schedule->update($data);
    }
}
