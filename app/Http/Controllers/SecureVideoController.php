<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

class SecureVideoController extends Controller
{
    /**
     * Generate a signed URL for a video to prevent direct hotlinking or downloads.
     */
    public function stream(Topic $topic)
    {
        // Check subscription
        if (!Auth::user()->hasActiveSubscription()) {
            abort(403, 'Suscripción activa requerida para ver videos.');
        }

        if (!$topic->video_url) {
            abort(404, 'Video no encontrado.');
        }

        // In a real implementation with S3, we would generate a temporary signed URL.
        // For local storage, we can use a controller that reads the file.
        // For YouTube/Vimeo (as per brief), we can't hide the URL easily without a proxy,
        // but we can at least ensure the user is authorized before showing the player.
        
        return view('courses.video-player', compact('topic'));
    }
}
