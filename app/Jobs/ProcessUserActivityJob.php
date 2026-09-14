<?php

namespace App\Jobs;

use App\Models\UserActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUserActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $activityData)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (isset($this->activityData['action'])) {
            // Si es una actualización de tiempo de estudio para otorgar puntos
            if ($this->activityData['action'] === 'update_study_time') {
                $user = \App\Models\User::find($this->activityData['user_id']);
                if ($user) {
                    $totalHours = $user->total_study_minutes / 60;
                    app(\App\Services\PointService::class)->awardForStudyTime($user, $totalHours);
                }
                return;
            }

            // Incremento de minutos de estudio desde el heartbeat
            if ($this->activityData['action'] === 'increment_study_minute') {
                $user = \App\Models\User::find($this->activityData['user_id']);
                if ($user) {
                    $user->increment('total_study_minutes', $this->activityData['minutes']);
                    
                    // También actualizamos el time_spent de la última actividad significativa en esta sesión
                    $lastActivity = \App\Models\UserActivity::where('user_id', $user->id)
                        ->where('session_id', $this->activityData['session_id'])
                        ->where('action_type', '!=', 'heartbeat')
                        ->latest()
                        ->first();
                    
                    if ($lastActivity) {
                        $lastActivity->increment('time_spent', $this->activityData['minutes'] * 60);
                    }
                }
                return;
            }
        }

        UserActivity::create($this->activityData);
    }
}
