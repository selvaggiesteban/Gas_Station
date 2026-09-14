<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;

class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $course;
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Course $course)
    {
        $this->user = $user;
        $this->course = $course;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Verify user has completed 100% of the course
            $totalTopics = $this->course->topics()->count();
            $completedTopics = $this->user->completedTopics()
                ->whereIn('topics.id', $this->course->topics()->pluck('id'))
                ->count();

            if ($completedTopics < $totalTopics) {
                Log::warning('Certificate generation skipped - course not completed', [
                    'user_id' => $this->user->id,
                    'course_id' => $this->course->id,
                    'completed' => $completedTopics,
                    'total' => $totalTopics,
                ]);
                return;
            }

            // Generate certificate data
            $certificateData = [
                'student_name' => $this->user->name,
                'course_name' => $this->course->title,
                'completion_date' => now()->format('d/m/Y'),
                'certificate_id' => $this->generateCertificateId(),
                'issue_date' => now()->format('F Y'),
            ];

            // Generate PDF
            $pdf = PDF::loadView('certificates.template', $certificateData);
            $pdf->setPaper('a4', 'landscape');

            // Store certificate
            $filename = "certificates/{$this->user->id}/{$this->course->id}_{$this->generateCertificateId()}.pdf";
            Storage::disk('local')->put($filename, $pdf->output());

            // Update user's certificate record
            // This would typically update a certificates table
            Log::info('Certificate generated successfully', [
                'user_id' => $this->user->id,
                'course_id' => $this->course->id,
                'path' => $filename,
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate generation failed', [
                'user_id' => $this->user->id,
                'course_id' => $this->course->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate unique certificate ID
     */
    private function generateCertificateId(): string
    {
        return 'ACAD-' . $this->course->id . '-' . $this->user->id . '-' . now()->format('Ymd');
    }
}
