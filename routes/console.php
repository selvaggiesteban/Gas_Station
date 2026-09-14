<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule subscription renewals to run daily at 6 AM
Schedule::command('subscriptions:renew')
    ->daily()
    ->at('06:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled subscription renewals failed');
    });

// Also check for failed subscriptions to retry every day at noon
Schedule::command('subscription:retry-failed')
    ->daily()
    ->at('12:00')
    ->withoutOverlapping();

// Check for expired subscriptions daily at midnight
Schedule::command('subscriptions:check-expiry')
    ->daily()
    ->at('00:00')
    ->withoutOverlapping();

// Auto-match users in battle queues every minute
Schedule::call(function () {
    app(\App\Services\MatchmakingService::class)->processMatchmaking();
})->everyMinute()
    ->name('matchmaking')
    ->withoutOverlapping();

// Prune old activity logs daily at 3:00 AM
Schedule::command('app:prune-activity-logs')
    ->daily()
    ->at('03:00')
    ->withoutOverlapping();

// Daily database backup at 02:00 AM
Schedule::command('app:backup')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();

// Auto-fetch Zoom recordings every 30 minutes
Schedule::call(function () {
    $meetings = \App\Models\ZoomMeeting::whereNull('recording_url')
        ->where(function ($q) {
            $q->where('status', \App\Models\ZoomMeeting::STATUS_FINISHED)
              ->orWhere('start_time', '<=', now()->subHours(2));
        })
        ->get();

    foreach ($meetings as $meeting) {
        try {
            $recording = app(\App\Services\ZoomService::class)->getRecording($meeting->meeting_id);
            if ($recording && !empty($recording['recording_files'])) {
                $recordingUrl = collect($recording['recording_files'])
                    ->where('status', 'completed')
                    ->whereIn('file_type', ['mp4', 'm4a'])
                    ->first()['play_url'] ?? null;
                if ($recordingUrl) {
                    $meeting->update([
                        'recording_url' => $recordingUrl,
                        'status' => \App\Models\ZoomMeeting::STATUS_FINISHED,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Auto-fetch recording failed', [
                'meeting_id' => $meeting->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->everyThirtyMinutes()
    ->name('zoom-recordings')
    ->withoutOverlapping();
