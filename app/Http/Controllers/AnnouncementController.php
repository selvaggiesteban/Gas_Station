<?php

namespace App\Http\Controllers;

use App\Models\AcademyAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Display list of announcements for students
     */
    public function index()
    {
        $user = Auth::user();
        $cuerpoIds = $user->cuerpos()->pluck('cuerpos.id');

        $announcements = AcademyAnnouncement::active()
            ->where(function ($q) use ($cuerpoIds) {
                $q->whereNull('target_cuerpos')
                  ->orWhere('target_cuerpos', '[]');
                foreach ($cuerpoIds as $id) {
                    $q->orWhereJsonContains('target_cuerpos', $id);
                }
            })
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        $readAnnouncementIds = $user->readAnnouncements ?? [];

        return view('announcements.index', compact('announcements', 'readAnnouncementIds'));
    }

    /**
     * Show a specific announcement
     */
    public function show(AcademyAnnouncement $announcement)
    {
        if (!$announcement->is_active) {
            abort(404);
        }

        // Mark as read for current user
        $user = Auth::user();
        $readAnnouncements = $user->read_announcements ?? [];
        if (!in_array($announcement->id, $readAnnouncements)) {
            $readAnnouncements[] = $announcement->id;
            $user->update(['read_announcements' => $readAnnouncements]);
        }

        return view('announcements.show', compact('announcement'));
    }
}
