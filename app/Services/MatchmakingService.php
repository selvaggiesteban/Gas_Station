<?php

namespace App\Services;

use App\Models\User;
use App\Models\Battle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchmakingService
{
    /**
     * User levels for matchmaking - Unified system
     */
    const LEVELS = [
        'Cobre' => ['min' => 0, 'max' => 999],
        'Bronce' => ['min' => 1000, 'max' => 2999],
        'Plata' => ['min' => 3000, 'max' => 5999],
        'Oro' => ['min' => 6000, 'max' => 8999],
        'Diamante' => ['min' => 9000, 'max' => PHP_INT_MAX],
    ];

    /**
     * Find an opponent for the given user
     */
    public function findOpponent(User $user, ?string $topicId = null): ?User
    {
        $userLevel = $this->getUserLevel($user);

        // 1. Try to find a match in the same level
        $opponent = $this->searchInLevel($user, $userLevel, $topicId);
        if ($opponent) return $opponent;

        // 2. If no match, search in adjacent levels
        $levelNames = array_keys(self::LEVELS);
        $userLevelIndex = array_search($userLevel, $levelNames);

        $adjacents = [];
        if ($userLevelIndex > 0) $adjacents[] = $levelNames[$userLevelIndex - 1]; 
        if ($userLevelIndex < count($levelNames) - 1) $adjacents[] = $levelNames[$userLevelIndex + 1];

        foreach ($adjacents as $adjLevel) {
            $opponent = $this->searchInLevel($user, $adjLevel, $topicId);
            if ($opponent) return $opponent;
        }

        // Add user to matchmaking queue
        $this->addToQueue($user, $userLevel, $topicId);

        return null;
    }

    private function searchInLevel(User $user, string $level, ?string $topicId = null): ?User
    {
        $opponentId = DB::table('matchmaking_queues')
            ->where('level', $level)
            ->where('topic_id', $topicId)
            ->where('user_id', '!=', $user->id)
            ->where('queued_at', '>=', now()->subHour())
            ->orderBy('queued_at', 'asc')
            ->value('user_id');

        if ($opponentId) {
            $opponent = User::find($opponentId);
            if ($opponent) {
                $this->removeFromQueue($user->id, $level, $topicId);
                $this->removeFromQueue($opponent->id, $level, $topicId);

                Log::info('Match found', [
                    'challenger_id' => $user->id,
                    'opponent_id' => $opponent->id,
                    'level' => $level,
                ]);

                return $opponent;
            }
        }

        return null;
    }

    public function getUserLevel(User $user): string
    {
        $points = $user->points ?? 0;
        foreach (self::LEVELS as $level => $range) {
            if ($points >= $range['min'] && $points <= $range['max']) {
                return $level;
            }
        }
        return 'Cobre';
    }

    public function addToQueue(User $user, string $level, ?string $topicId = null): void
    {
        try {
            DB::table('matchmaking_queues')->updateOrInsert(
                ['user_id' => $user->id, 'level' => $level, 'topic_id' => $topicId],
                ['queued_at' => now(), 'updated_at' => now()]
            );
        } catch (\Exception $e) {
            Log::error('Matchmaking DB error (addToQueue): ' . $e->getMessage());
        }
    }

    public function removeFromQueue(int $userId, ?string $level = null, ?string $topicId = null): void
    {
        try {
            $query = DB::table('matchmaking_queues')->where('user_id', $userId);
            if ($level) $query->where('level', $level);
            if ($topicId) $query->where('topic_id', $topicId);
            $query->delete();
        } catch (\Exception $e) {
            Log::error('Matchmaking DB error (removeFromQueue): ' . $e->getMessage());
        }
    }

    public function getQueueStats(): array
    {
        $stats = [];
        foreach (array_keys(self::LEVELS) as $level) {
            $count = DB::table('matchmaking_queues')
                ->where('level', $level)
                ->where('queued_at', '>=', now()->subHour())
                ->count();
            
            $stats[$level] = ['queue_length' => $count];
        }
        return $stats;
    }

    public function processMatchmaking(): void
    {
        foreach (array_keys(self::LEVELS) as $level) {
            $queuedUsers = DB::table('matchmaking_queues')
                ->where('level', $level)
                ->where('queued_at', '>=', now()->subHour())
                ->orderBy('queued_at', 'asc')
                ->pluck('user_id')
                ->toArray();

            for ($i = 0; $i < count($queuedUsers) - 1; $i += 2) {
                $user1 = User::find($queuedUsers[$i]);
                $user2 = User::find($queuedUsers[$i + 1]);

                if ($user1 && $user2) {
                    $topicId = \App\Models\Topic::inRandomOrder()->first()?->id;

                    Battle::create([
                        'challenger_id' => $user1->id,
                        'opponent_id' => $user2->id,
                        'topic_id' => $topicId,
                        'status' => 'pending',
                    ]);

                    $this->removeFromQueue($user1->id, $level);
                    $this->removeFromQueue($user2->id, $level);

                    Log::info('Auto-match created', [
                        'user1' => $user1->id,
                        'user2' => $user2->id,
                        'level' => $level,
                    ]);
                }
            }
        }
    }
}
