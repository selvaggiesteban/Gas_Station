<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Generate and download certificate for a completed course
     */
    public function download(Course $course)
    {
        $user = Auth::user();

        // Check if user has completed the course (100% progress)
        $progress = $user->getCourseProgress($course);

        if ($progress < 100) {
            return redirect()->back()
                ->with('error', 'Debes completar el 100% del curso para descargar el certificado.');
        }

        // Check if all tests are passed
        $allTestsPassed = $this->checkAllTestsPassed($user, $course);

        if (!$allTestsPassed) {
            return redirect()->back()
                ->with('error', 'Debes aprobar todos los exámenes del curso para obtener el certificado.');
        }

        // Generate PDF certificate
        $pdf = $this->generateCertificate($user, $course);

        $filename = "certificado_{$course->slug}_{$user->id}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Check if user has passed all tests in the course
     */
    private function checkAllTestsPassed($user, $course): bool
    {
        foreach ($course->blocks as $block) {
            foreach ($block->tests as $test) {
                $hasPassed = TestAttempt::where('user_id', $user->id)
                    ->where('test_id', $test->id)
                    ->where('passed', true)
                    ->exists();

                if (!$hasPassed) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Generate the certificate PDF
     */
    private function generateCertificate($user, $course)
    {
        // Using Laravel PDF package (barryvdh/laravel-dompdf)
        $pdf = app('dompdf.wrapper');

        $data = [
            'studentName' => $user->name,
            'courseName' => $course->title,
            'completionDate' => now()->format('d/m/Y'),
            'certificateId' => $this->generateCertificateId($user, $course),
        ];

        $pdf->loadView('certificates.template', $data);

        return $pdf;
    }

    /**
     * Generate unique certificate ID
     */
    private function generateCertificateId($user, $course): string
    {
        return 'ACAD-' . strtoupper($course->slug) . '-' . $user->id . '-' . now()->format('Ymd');
    }
}
