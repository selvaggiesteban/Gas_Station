<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureContentController extends Controller
{
    /**
     * Generate a signed URL for PDF download (expires in 1 hour)
     */
    public function getSignedPdfUrl(Topic $topic)
    {
        $user = auth()->user();
        $course = $topic->block->course;

        // Check if user has active subscription for THIS course or is staff
        if (!$user->hasAccessToCourse($course)) {
            abort(403, 'Suscripción activa al curso requerida para acceder a este contenido');
        }

        // Check if topic is available
        if (!$user->hasRole(['Admin', 'Content Manager', 'Teacher']) && !$topic->isAvailable()) {
            abort(403, 'Contenido no disponible todavía');
        }

        // Check if PDF exists
        if (!$topic->pdf_path) {
            abort(404, 'PDF not found');
        }

        // Generate signed URL valid for 1 hour
        $signedUrl = URL::temporarySignedRoute(
            'seguro.pdf.descargar',
            now()->addHour(),
            ['topic' => $topic] // Laravel will use getRouteKeyName() which is 'slug'
        );

        return response()->json([
            'signed_url' => $signedUrl,
            'expires_at' => now()->addHour()->toIso8601String(),
        ]);
    }

    /**
     * Download PDF via signed URL
     */
    public function downloadPdf(Request $request, Topic $topic): StreamedResponse
    {
        // Validate signed URL
        if (!$request->hasValidSignature()) {
            abort(401, 'URL inválida o expirada');
        }

        $user = auth()->user();
        if (!$user || !$user->hasAccessToCourse($topic->block->course)) {
            abort(403, 'Suscripción activa requerida para acceder a este contenido');
        }

        $path = $topic->pdf_path ?: $topic->pdf_url;
        $title = $topic->title;

        // If a specific version is requested
        if ($request->has('version')) {
            $version = \App\Models\TopicPdfVersion::find($request->version);
            if ($version && $version->topic_id === $topic->id) {
                $path = $version->file_path;
                if ($version->title) {
                    $title = $version->title;
                } else {
                    $title = "{$topic->title}_v{$version->version_number}";
                }
            }
        }

        // Check if PDF exists
        if (!$path) {
            abort(404, 'PDF not found');
        }

        // Check if file exists in storage (default disk)
        if (Storage::exists($path)) {
            return Storage::download($path, "{$title}.pdf");
        }

        // Check if file exists in storage (public disk)
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, "{$title}.pdf");
        }

        abort(404, 'File not found in storage');
    }

    /**
     * Stream video content (if needed)
     */
    public function streamVideo(Request $request, Topic $topic)
    {
        // Validate signed URL
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid or expired URL');
        }

        // Check if topic is available
        if (!$topic->isAvailable()) {
            abort(403, 'Content not yet available');
        }

        // For external videos (YouTube, Vimeo), redirect
        if (str_contains($topic->video_url ?? '', ['youtube.com', 'youtu.be', 'vimeo.com'])) {
            return redirect()->away($topic->video_url);
        }

        // For local videos, stream
        if ($topic->video_url && Storage::exists($topic->video_url)) {
            return response()->file(Storage::path($topic->video_url));
        }

        abort(404, 'Video not found');
    }
}
