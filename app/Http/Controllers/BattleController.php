<?php

namespace App\Http\Controllers;

use App\Actions\CreateBattleAction;
use App\Actions\AcceptBattleAction;
use App\Models\Battle;
use App\Models\User;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Actions\CheckLevelUpAction;
use App\Http\Requests\SendBattleChallengeRequest;
use App\Http\Requests\SubmitBattleScoreRequest;

class BattleController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $battles = Battle::where('challenger_id', $user->id)
            ->orWhere('opponent_id', $user->id)
            ->with(['challenger', 'opponent', 'topic'])
            ->latest()
            ->paginate(15);

        return view('battles.index', compact('battles'));
    }

    public function create()
    {
        $users = User::where('id', '!=', Auth::id())
            ->role('Student')
            ->select('id', 'name', 'points')
            ->orderBy('points', 'desc')
            ->paginate(20);
            
        $topics = Topic::select('id', 'title', 'block_id')
            ->where('is_active', true)
            ->with('block:id,title')
            ->get();
            
        return view('battles.create', compact('users', 'topics'));
    }

    public function store(SendBattleChallengeRequest $request, CreateBattleAction $createBattle)
    {
        try {
            $battle = $createBattle->execute($request->validated());
            return redirect()->route('battles.show', $battle)->with('success', '¡Desafío enviado!');
        } catch (\DomainException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function accept(Battle $battle, AcceptBattleAction $acceptBattle)
    {
        if ($battle->opponent_id !== Auth::id()) {
            abort(403);
        }

        $acceptBattle->execute($battle);
        return redirect()->route('battles.show', $battle)->with('success', '¡Desafío aceptado! Ya puedes jugar.');
    }

    public function show(Battle $battle)
    {
        $user = Auth::user();

        if ($battle->challenger_id !== $user->id && $battle->opponent_id !== $user->id) {
            abort(403, 'No estás autorizado para ver esta batalla.');
        }

        $battle->load(['challenger', 'opponent', 'topic']);
        
        $userFinished = ($battle->challenger_id === $user->id && $battle->challenger_score !== null) ||
                       ($battle->opponent_id === $user->id && $battle->opponent_score !== null);

        return view('battles.show', compact('battle', 'userFinished'));
    }

    /**
     * Start playing the battle
     */
    public function play(Battle $battle)
    {
        $user = Auth::user();

        if ($battle->status !== 'accepted') {
            return redirect()->route('battles.show', $battle)->with('error', 'La batalla aún no ha sido aceptada o ya terminó.');
        }

        // Check if user already played
        if (($battle->challenger_id === $user->id && $battle->challenger_score !== null) ||
            ($battle->opponent_id === $user->id && $battle->opponent_score !== null)) {
            return redirect()->route('battles.show', $battle)->with('info', 'Ya has completado tu participación en esta batalla.');
        }

        $questionIds = $battle->question_ids;
        if (empty($questionIds)) {
            return redirect()->route('battles.show', $battle)->with('error', 'No hay preguntas configuradas para esta batalla.');
        }

        $questions = Question::whereIn('id', $questionIds)
            ->with('options')
            ->get()
            ->sortBy(function($question) use ($questionIds) {
                return array_search($question->id, $questionIds);
            });

        return view('battles.play', compact('battle', 'questions'));
    }

    /**
     * Submit score for a battle
     */
    public function submitScore(Request $request, Battle $battle)
    {
        $user = Auth::user();
        $answers = $request->input('answers', []);
        
        if ($battle->status !== 'accepted') {
            return redirect()->route('battles.show', $battle)->with('error', 'Esta batalla no está activa.');
        }

        // Calculate score
        $score = 0;
        $questionIds = $battle->question_ids;
        $questions = Question::whereIn('id', $questionIds)->get();

        foreach ($questions as $question) {
            $answerId = $answers[$question->id] ?? null;
            if ($answerId) {
                $option = Option::find($answerId);
                if ($option && $option->is_correct) {
                    $score += 10; // 10 points per correct answer
                }
            }
        }

        if ($battle->challenger_id === $user->id) {
            $battle->challenger_score = $score;
        } else {
            $battle->opponent_score = $score;
        }

        $battle->save();

        // Check if both finished
        if ($battle->challenger_score !== null && $battle->opponent_score !== null) {
            $battle->status = 'completed';

            if ($battle->challenger_score > $battle->opponent_score) {
                $battle->winner_id = $battle->challenger_id;
            } elseif ($battle->opponent_score > $battle->challenger_score) {
                $battle->winner_id = $battle->opponent_id;
            }
            
            $battle->save();

            if ($battle->winner_id) {
                $winner = User::find($battle->winner_id);
                app(\App\Services\PointService::class)->award($winner, 100, 'battle_win', 'Battle', $battle->id);
                $winner->increment('battles_won');
            }
        }

        return redirect()->route('battles.show', $battle)->with('success', '¡Batalla completada! Tu puntuación: ' . $score);
    }
}
