<?php

namespace App\Http\Controllers\ContentManager;

use App\Http\Controllers\Controller;
use App\Models\AcademyAnnouncement;
use App\Http\Requests\ContentManager\StoreAnnouncementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = AcademyAnnouncement::latest()->paginate(15);
        return view('content-manager.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('content-manager.announcements.create');
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $data = $request->validated();
        $data['published_by'] = Auth::id();
        $data['published_at'] = now();
        $data['target_cuerpos'] = $request->input('target_cuerpos') ?: null;

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('announcements', 'public');
        }

        $announcement = AcademyAnnouncement::create($data);

        // N3: enviar email a alumnos afectados por el alcance
        $this->sendAnnouncementEmail($announcement);

        return redirect()->route('content-manager.announcements.index')
            ->with('success', 'Anuncio publicado correctamente.');
    }

    public function edit(AcademyAnnouncement $announcement)
    {
        return view('content-manager.announcements.edit', compact('announcement'));
    }

    public function update(StoreAnnouncementRequest $request, AcademyAnnouncement $announcement)
    {
        $data = $request->validated();
        $data['target_cuerpos'] = $request->input('target_cuerpos') ?: null;

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('announcements', 'public');
        }

        $announcement->update($data);

        return redirect()->route('content-manager.announcements.index')
            ->with('success', 'Anuncio actualizado.');
    }

    public function destroy(AcademyAnnouncement $announcement)
    {
        $announcement->delete();
        return redirect()->route('content-manager.announcements.index')
            ->with('success', 'Anuncio eliminado.');
    }

    /**
     * Envía email de anuncio a usuarios según alcance (Todos o Cuerpos específicos).
     * N3: motor Resend, N4: filtrado por cuerpo.
     */
    private function sendAnnouncementEmail(AcademyAnnouncement $announcement): void
    {
        $query = \App\Models\User::query()->whereNotNull('email');

        if (!empty($announcement->target_cuerpos)) {
            $query->whereHas('cuerpos', fn($q) => $q->whereIn('cuerpos.id', $announcement->target_cuerpos));
        }

        $users = $query->limit(500)->get(); // batch para no saturar

        foreach ($users as $user) {
            try {
                $user->notify(new \App\Notifications\AnnouncementPublished($announcement));
            } catch (\Exception $e) {
                \Log::warning('Announcement email failed: ' . $e->getMessage());
            }
        }
    }
}
