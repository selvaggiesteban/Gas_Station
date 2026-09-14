<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\ZoomMeeting;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ZoomController extends Controller
{
    private ZoomService $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * List all Zoom meetings for the authenticated user
     */
    public function index()
    {
        $meetings = ZoomMeeting::with(['topic', 'creator'])
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        return view('zoom.index', compact('meetings'));
    }

    /**
     * Show upcoming meetings for this week
     */
    public function thisWeek()
    {
        $meetings = ZoomMeeting::with(['topic', 'creator'])
            ->where('status', ZoomMeeting::STATUS_SCHEDULED)
            ->thisWeek()
            ->orderBy('start_time', 'asc')
            ->get();

        return view('zoom.this-week', compact('meetings'));
    }

    /**
     * Show form to create a new meeting
     */
    public function create()
    {
        $this->authorize('create', ZoomMeeting::class);

        if (!$this->zoomService->isConfigured()) {
            return redirect()->back()
                ->with('error', 'Zoom no está configurado. Contacta al administrador.');
        }

        $topics = Topic::where('is_active', true)->get();

        return view('zoom.create', compact('topics'));
    }

    /**
     * Store a new Zoom meeting
     */
    public function store(Request $request)
    {
        $this->authorize('create', ZoomMeeting::class);

        $validated = $request->validate([
            'topic_id' => 'required|exists:topics,id',
            'start_time' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'timezone' => 'nullable|string|max:50',
        ]);

        $topic = Topic::findOrFail($validated['topic_id']);

        // Create meeting via Zoom API
        $result = $this->zoomService->createMeeting([
            'title' => "Clase: {$topic->title}",
            'start_time' => $validated['start_time'],
            'duration_minutes' => $validated['duration_minutes'],
            'timezone' => $validated['timezone'] ?? 'Europe/Madrid',
        ]);

        if (!$result['success']) {
            return redirect()->back()
                ->with('error', 'Error al crear la reunión de Zoom: ' . $result['error'])
                ->withInput();
        }

        // Save meeting to database
        $meeting = ZoomMeeting::create([
            'topic_id' => $validated['topic_id'],
            'meeting_id' => $result['meeting_id'],
            'join_url' => $result['join_url'],
            'start_url' => $result['start_url'],
            'start_time' => $validated['start_time'],
            'duration_minutes' => $validated['duration_minutes'],
            'timezone' => $validated['timezone'] ?? 'Europe/Madrid',
            'password' => $result['password'],
            'status' => ZoomMeeting::STATUS_SCHEDULED,
            'created_by' => Auth::id(),
        ]);

        Log::info('Zoom meeting created', [
            'meeting_id' => $meeting->id,
            'zoom_meeting_id' => $result['meeting_id'],
            'topic_id' => $topic->id,
        ]);

        return redirect()->route('zoom.index')
            ->with('success', 'Clase programada correctamente en Zoom.');
    }

    /**
     * Show a specific meeting
     */
    public function show(ZoomMeeting $meeting)
    {
        $user = Auth::user();

        // Check if user has active subscription
        if (!$user->subscription || !$user->subscription->isActive()) {
            abort(403, 'Necesitas una suscripción activa para ver las videoclases.');
        }

        $meeting->load(['topic', 'creator']);

        return view('zoom.show', compact('meeting'));
    }

    /**
     * Join a meeting as participant
     */
    public function join(ZoomMeeting $meeting)
    {
        // Check if meeting is accessible
        if ($meeting->status === ZoomMeeting::STATUS_CANCELLED) {
            return redirect()->back()
                ->with('error', 'Esta clase ha sido cancelada.');
        }

        // Check if meeting has started or is about to start
        if (!$meeting->isUpcoming() && !$meeting->isLive()) {
            if ($meeting->start_time->isFuture()) {
                $timeUntil = $meeting->start_time->diffForHumans();
                return redirect()->back()
                    ->with('error', "La clase comienza en {$timeUntil}.");
            }
        }

        // Redirect to Zoom
        return redirect()->away($meeting->join_url);
    }

    /**
     * Start a meeting as host
     */
    public function start(ZoomMeeting $meeting)
    {
        $this->authorize('update', $meeting);

        // Update meeting status
        $meeting->update(['status' => ZoomMeeting::STATUS_LIVE]);

        // Redirect to Zoom start URL
        return redirect()->away($meeting->start_url);
    }

    /**
     * Cancel a meeting
     */
    public function cancel(ZoomMeeting $meeting)
    {
        $this->authorize('delete', $meeting);

        // Delete from Zoom
        if ($meeting->meeting_id) {
            $this->zoomService->deleteMeeting($meeting->meeting_id);
        }

        // Update status
        $meeting->update(['status' => ZoomMeeting::STATUS_CANCELLED]);

        Log::info('Zoom meeting cancelled', ['meeting_id' => $meeting->id]);

        return redirect()->route('zoom.index')
            ->with('success', 'Clase cancelada correctamente.');
    }

    /**
     * Mark meeting as finished (update recording URL)
     */
    public function finish(Request $request, ZoomMeeting $meeting)
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'recording_url' => 'nullable|url|max:1000',
        ]);

        $meeting->update([
            'status' => ZoomMeeting::STATUS_FINISHED,
            'recording_url' => $validated['recording_url'] ?? null,
        ]);

        return redirect()->route('zoom.index')
            ->with('success', 'Clase marcada como finalizada.');
    }

    /**
     * Get meetings for a specific topic
     */
    public function byTopic(Topic $topic)
    {
        $meetings = ZoomMeeting::where('topic_id', $topic->id)
            ->where('status', ZoomMeeting::STATUS_SCHEDULED)
            ->where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->get();

        return view('zoom.by-topic', compact('meetings', 'topic'));
    }
}