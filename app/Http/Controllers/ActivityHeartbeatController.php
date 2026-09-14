<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessUserActivityJob;

class ActivityHeartbeatController extends Controller
{
    /**
     * Telemetría blindada contra fallos de infraestructura (Error 500 safe)
     */
    public function ping(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['status' => 'unauthorized'], 401);

        try {
            $sessionId = session()->getId();
            $cacheKey = "user:{$user->id}:session:{$sessionId}:study_time";

            // Incrementamos usando Cache (más genérico que Redis)
            $currentSeconds = Cache::increment($cacheKey, 30);
            if ($currentSeconds === 30) {
                Cache::put($cacheKey, 30, 300); // Set expiry on first increment
            }

            // Cada 60 segundos acumulados, persistimos a MySQL de forma asíncrona
            if ($currentSeconds > 0 && $currentSeconds % 60 === 0) {
                ProcessUserActivityJob::dispatch([
                    'action' => 'increment_study_minute',
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'minutes' => 1
                ])->onQueue('telemetry');
            }

            return response()->json(['status' => 'ok', 'buffered_seconds' => $currentSeconds]);

        } catch (\Exception $e) {
            // SILENT FAIL
            Log::debug('Heartbeat fallback engaged.', ['error' => $e->getMessage()]);
            
            return response()->json([
                'status' => 'degraded_mode',
                'message' => 'Tracking unavailable'
            ]);
        }
    }
}
