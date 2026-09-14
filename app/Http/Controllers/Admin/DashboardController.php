<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_courses' => Course::count(),
            'recent_users' => User::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
