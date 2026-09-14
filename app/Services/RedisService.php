<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisService
{
    /**
     * Get leaderboard for a specific period
     *
     * @param string $period 'weekly', 'monthly', 'all_time'
     * @param int $limit Number of users to return
     * @return array
     */
    public function getLeaderboard(string $period = 'weekly', int $limit = 100): array
    {
        $key = "leaderboard:{$period}";

        try {
            $results = Redis::zrevrange($key, 0, $limit - 1, 'WITHSCORES');

            $leaderboard = [];
            foreach ($results as $userId => $score) {
                $leaderboard[] = [
                    'user_id' => (int) $userId,
                    'score' => (float) $score,
                    'rank' => $this->getUserRank((int) $userId, $period),
                ];
            }

            return $leaderboard;
        } catch (\Exception $e) {
            Log::error("Failed to get leaderboard: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Update user score in leaderboard
     *
     * @param int $userId
     * @param int $points
     * @param string $period
     * @return bool
     */
    public function updateUserScore(int $userId, int $points, string $period = 'weekly'): bool
    {
        $key = "leaderboard:{$period}";

        try {
            Redis::zincrby($key, $points, $userId);

            // Set expiry for weekly and monthly leaderboards
            if (in_array($period, ['weekly', 'monthly'])) {
                $ttl = $period === 'weekly' ? 7 * 24 * 60 * 60 : 30 * 24 * 60 * 60;
                Redis::expire($key, $ttl);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update user score: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get user's rank in leaderboard
     *
     * @param int $userId
     * @param string $period
     * @return int|null
     */
    public function getUserRank(int $userId, string $period = 'weekly'): ?int
    {
        $key = "leaderboard:{$period}";

        try {
            $rank = Redis::zrevrank($key, $userId);
            return $rank !== null ? $rank + 1 : null;
        } catch (\Exception $e) {
            Log::error("Failed to get user rank: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get user's score
     *
     * @param int $userId
     * @param string $period
     * @return float|null
     */
    public function getUserScore(int $userId, string $period = 'weekly'): ?float
    {
        $key = "leaderboard:{$period}";

        try {
            $score = Redis::zscore($key, $userId);
            return $score !== false ? (float) $score : null;
        } catch (\Exception $e) {
            Log::error("Failed to get user score: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Cache battle queue entry
     *
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function cacheBattleQueue(int $userId, array $data): bool
    {
        try {
            Redis::hset("battle:queue", $userId, json_encode($data));
            Redis::expire("battle:queue", 3600); // 1 hour expiry
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to cache battle queue: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get all users in battle queue
     *
     * @return array
     */
    public function getBattleQueue(): array
    {
        try {
            $queue = Redis::hgetall("battle:queue");
            return array_map(fn($item) => json_decode($item, true), $queue);
        } catch (\Exception $e) {
            Log::error("Failed to get battle queue: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Remove user from battle queue
     *
     * @param int $userId
     * @return bool
     */
    public function removeFromBattleQueue(int $userId): bool
    {
        try {
            Redis::hdel("battle:queue", $userId);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to remove from battle queue: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Check if user is in battle queue
     *
     * @param int $userId
     * @return bool
     */
    public function isInBattleQueue(int $userId): bool
    {
        try {
            return Redis::hexists("battle:queue", $userId);
        } catch (\Exception $e) {
            Log::error("Failed to check battle queue: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Reset leaderboard for a period
     *
     * @param string $period
     * @return bool
     */
    public function resetLeaderboard(string $period = 'weekly'): bool
    {
        try {
            Redis::del("leaderboard:{$period}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to reset leaderboard: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Cache data with TTL
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttlSeconds
     * @return bool
     */
    public function cache(string $key, $data, int $ttlSeconds = 3600): bool
    {
        try {
            Redis::setex($key, $ttlSeconds, serialize($data));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to cache data: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get cached data
     *
     * @param string $key
     * @return mixed|null
     */
    public function getCache(string $key)
    {
        try {
            $data = Redis::get($key);
            return $data ? json_decode($data, true) : null;
        } catch (\Exception $e) {
            Log::error("Failed to get cache: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Delete cached data
     *
     * @param string $key
     * @return bool
     */
    public function deleteCache(string $key): bool
    {
        try {
            Redis::del($key);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete cache: {$e->getMessage()}");
            return false;
        }
    }
}
