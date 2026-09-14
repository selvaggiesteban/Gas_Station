<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudyRoundController extends Controller
{
    /**
     * Start a new study round for the user.
     */
    public function startNewRound()
    {
        $user = Auth::user();
        
        DB::transaction(function () use ($user) {
            $user->increment('current_study_round');
            
            // We don't delete old progress, it stays in topic_user with the old round_number.
            // But we might want to "clear" the active view for the new round.
            // The completion logic will now use the new round number.
        });

        return redirect()->back()->with('success', "¡Has iniciado la vuelta número {$user->current_study_round}! Tu progreso visual se ha reiniciado para esta nueva etapa.");
    }
}
