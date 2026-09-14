<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, PointService $pointService)
    {
        $type = $request->query('type', 'monthly'); // Default to monthly as requested

        if ($type === 'global') {
            $leaders = User::role('Student')
                ->orderByDesc('points')
                ->take(10)
                ->get();
        } else {
            $leaders = $pointService->getMonthlyRanking(10);
        }

        return view('gamification.leaderboard', compact('leaders', 'type'));
    }
}
