<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\ActivityLoggerService;
use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    protected $loggerService;

    public function __construct(ActivityLoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    /**
     * Display a listing of the user's activity.
     */
    public function index(User $user, Request $request)
    {
        $query = UserActivity::forUser($user->id)
            ->recent();

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('action_type', $request->type);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $activities = $query->paginate(50)->withQueryString();

        // Attach human readable descriptions
        $activities->getCollection()->transform(function ($activity) {
            $activity->description = $this->loggerService->getActionDescription(
                $activity->route_name, 
                $activity->url
            );
            return $activity;
        });

        return view('admin.users.activity', compact('user', 'activities'));
    }
}
