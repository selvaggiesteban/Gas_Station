<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Jobs\GenerateCertificateJob;
use App\Models\Certificate;
use Illuminate\Support\Facades\Log;

class GenerateCertificateForCompletedCourse
{
    /**
     * Handle the event.
     */
    public function handle(CourseCompleted $event): void
    {
        $user = $event->user;
        $course = $event->course;

        Log::info("Processing course completion for user #{$user->id}, course #{$course->id}");

        // Check if certificate already exists
        $existingCertificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingCertificate) {
            Log::info("Certificate already exists for user #{$user->id}, course #{$course->id}");
            return;
        }

        // Dispatch job to generate certificate
        GenerateCertificateJob::dispatch($user, $course, $event->finalScore);

        Log::info("Certificate generation job dispatched for user #{$user->id}, course #{$course->id}");

        // Notify user
        $user->notify(new \App\Notifications\CertificateAvailable($course));
    }
}
