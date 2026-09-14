<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\LevelUpNotification;
use Illuminate\Support\Facades\Log;

class CheckLevelUpAction
{
    /**
     * Level thresholds according to documentation
     * Cobre → Bronce → Plata → Oro → Diamante
     */
    protected array $levelThresholds = [
        'Cobre' => ['min' => 0, 'max' => 999, 'level' => 1, 'milestones' => []],
        'Bronce' => [
            'min' => 1000, 
            'max' => 2999, 
            'level' => 2, 
            'milestones' => [
                'classes' => 10,
                'tests' => 4
            ]
        ],
        'Plata' => [
            'min' => 3000, 
            'max' => 5999, 
            'level' => 3, 
            'milestones' => [
                'tests' => 15,
                'supuestos' => 2,
                'mean_score' => 60,
                'mean_count' => 8
            ]
        ],
        'Oro' => [
            'min' => 6000, 
            'max' => 8999, 
            'level' => 4, 
            'milestones' => [
                'tests' => 30,
                'supuestos' => 5,
                'mean_score' => 65,
                'mean_count' => 10
            ]
        ],
        'Diamante' => [
            'min' => 9000, 
            'max' => PHP_INT_MAX, 
            'level' => 5, 
            'milestones' => [
                'tests' => 45,
                'supuestos' => 8,
                'mean_score' => 70,
                'mean_count' => 12
            ]
        ],
    ];

    public function execute(User $user): array
    {
        $currentRank = $user->level ?? 'Cobre';
        $currentLevel = 1;
        
        foreach ($this->levelThresholds as $rank => $data) {
            if ($rank === $currentRank) {
                $currentLevel = $data['level'];
                break;
            }
        }

        $points = $user->points ?? 0;
        $newLevel = $this->calculateLevelWithMilestones($user);

        $result = [
            'leveled_up' => false,
            'new_level' => $currentLevel,
            'new_rank' => $this->getRankFromLevel($currentLevel),
            'unlocked_items' => [],
        ];

        if ($newLevel > $currentLevel) {
            $oldLevel = $currentLevel;
            $oldRank = $this->getRankFromLevel($oldLevel);
            $newRank = $this->getRankFromLevel($newLevel);

            $user->update(['level' => $newRank]);

            Log::info("User #{$user->id} leveled up from {$oldRank} (level {$oldLevel}) to {$newRank} (level {$newLevel})");

            // Check and unlock new items
            $unlockedItems = $user->checkAndUnlockItems();

            // Send notification
            $user->notify(new LevelUpNotification($newLevel, $unlockedItems, $newRank));

            $result = [
                'leveled_up' => true,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'old_rank' => $oldRank,
                'new_rank' => $newRank,
                'unlocked_items' => $unlockedItems,
            ];
        }

        return $result;
    }

    /**
     * Calculate level based on points and milestones
     */
    protected function calculateLevelWithMilestones(User $user): int
    {
        $points = $user->points ?? 0;
        $highestLevel = 1;

        foreach ($this->levelThresholds as $rank => $data) {
            if ($points < $data['min']) {
                continue;
            }

            // Check milestones for this level
            if ($this->checkMilestones($user, $data['milestones'])) {
                $highestLevel = max($highestLevel, $data['level']);
            }
        }

        return $highestLevel;
    }

    /**
     * Check if user meets milestones
     */
    protected function checkMilestones(User $user, array $milestones): bool
    {
        if (empty($milestones)) {
            return true;
        }

        // Classes viewed
        if (isset($milestones['classes']) && ($user->total_classes_viewed ?? 0) < $milestones['classes']) {
            return false;
        }

        // Tests done
        if (isset($milestones['tests']) && ($user->total_tests_completed ?? 0) < $milestones['tests']) {
            return false;
        }

        // Supuestos entregados
        if (isset($milestones['supuestos']) && ($user->total_supuestos_submitted ?? 0) < $milestones['supuestos']) {
            return false;
        }

        // Mean score of last N tests
        if (isset($milestones['mean_score'])) {
            $count = $milestones['mean_count'] ?? 10;
            $attempts = $user->testAttempts()
                ->whereNotNull('completed_at')
                ->latest()
                ->take($count)
                ->get();
            
            if ($attempts->count() === 0) {
                return false;
            }

            $mean = $attempts->avg('score');
            
            if ($mean < $milestones['mean_score']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get rank name from level number
     */
    protected function getRankFromLevel(int $level): string
    {
        foreach ($this->levelThresholds as $rank => $data) {
            if ($data['level'] === $level) {
                return $rank;
            }
        }

        return 'Cobre';
    }

    /**
     * Get current rank based on points
     */
    public function getCurrentRank(int $points): string
    {
        foreach ($this->levelThresholds as $rank => $data) {
            if ($points >= $data['min'] && $points <= $data['max']) {
                return $rank;
            }
        }

        return 'Cobre';
    }

    /**
     * Get points required for next level
     */
    public function getPointsForNextLevel(User $user): ?int
    {
        $currentRank = $this->getCurrentRank($user->points ?? 0);
        $currentLevel = $this->levelThresholds[$currentRank]['level'] ?? 1;

        // Find next rank
        foreach ($this->levelThresholds as $rank => $data) {
            if ($data['level'] === $currentLevel + 1) {
                return $data['min'];
            }
        }

        return null; // Already at max level
    }

    /**
     * Get progress to next level as percentage
     */
    public function getProgressToNextLevel(User $user): int
    {
        $points = $user->points ?? 0;
        $currentRank = $this->getCurrentRank($points);
        $currentData = $this->levelThresholds[$currentRank];

        $currentMin = $currentData['min'];
        $currentMax = $currentData['max'];

        // If at diamante (max level), return 100%
        if ($currentRank === 'Diamante') {
            return 100;
        }

        // Calculate progress within current rank
        $range = $currentMax - $currentMin;
        if ($range <= 0) {
            return 100;
        }

        $progress = (($points - $currentMin) / $range) * 100;

        return min(100, max(0, (int) $progress));
    }

    /**
     * Get all level thresholds
     */
    public function getLevelThresholds(): array
    {
        return $this->levelThresholds;
    }
}
