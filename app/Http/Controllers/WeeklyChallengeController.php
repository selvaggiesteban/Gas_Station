<?php

namespace App\Http\Controllers;

use App\Models\WeeklyChallenge;
use App\Models\ChallengeParticipation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\StoreWeeklyChallengeRequest;

class WeeklyChallengeController extends Controller
{
    public function index()
    {
        // Admin or Content Manager should see the management view
        if (Auth::user()->hasAnyRole(['Admin', 'Content Manager'])) {
            return $this->adminIndex();
        }

        $challenges = WeeklyChallenge::active()->latest()->paginate(10);
        $userParticipations = Auth::user()->challengeParticipations()
            ->with('challenge')
            ->get()
            ->keyBy('weekly_challenge_id');

        return view('challenges.index', compact('challenges', 'userParticipations'));
    }

    /**
     * Show the current active challenge
     */
    public function current()
    {
        $challenge = WeeklyChallenge::active()
            ->where('end_date', '>', now())
            ->latest()
            ->first();

        if (!$challenge) {
            return view('challenges.none');
        }

        $participation = ChallengeParticipation::where('user_id', Auth::id())
            ->where('weekly_challenge_id', $challenge->id)
            ->first();

        return view('challenges.show', compact('challenge', 'participation'));
    }

    public function adminIndex()
    {
        $challenges = WeeklyChallenge::latest()->paginate(15);
        $goalTypes = [
            WeeklyChallenge::GOAL_POINTS => 'Puntos',
            WeeklyChallenge::GOAL_POMODOROS => 'Pomodoros',
            WeeklyChallenge::GOAL_TESTS_COMPLETED => 'Tests Completados',
            WeeklyChallenge::GOAL_BATTLES_WON => 'Batallas Ganadas',
            WeeklyChallenge::GOAL_TOPICS_COMPLETED => 'Temas Completados',
        ];

        return view('admin.challenges.index', compact('challenges', 'goalTypes'));
    }

    public function store(StoreWeeklyChallengeRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        
        WeeklyChallenge::create($data);

        return redirect()->back()->with('success', 'Reto creado correctamente.');
    }

    public function update(StoreWeeklyChallengeRequest $request, WeeklyChallenge $challenge)
    {
        $challenge->update($request->validated());

        return redirect()->back()->with('success', 'Reto actualizado correctamente.');
    }

    public function destroy(WeeklyChallenge $challenge)
    {
        $challenge->delete();
        return redirect()->back()->with('success', 'Reto eliminado.');
    }

    public function participate(WeeklyChallenge $challenge)
    {
        if (!$challenge->is_active || now()->gt($challenge->end_date)) {
            return redirect()->back()->with('error', 'Este desafío ya no está activo.');
        }

        ChallengeParticipation::firstOrCreate([
            'user_id' => Auth::id(),
            'weekly_challenge_id' => $challenge->id,
        ], [
            'current_progress' => 0,
            'is_completed' => false,
        ]);

        return redirect()->back()->with('success', '¡Te has unido al desafío! Buena suerte.');
    }
}
