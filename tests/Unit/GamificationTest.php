<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\PointLog;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that PointService@awardForStudyTime correctly increments points at 10 hours milestone.
     */
    public function test_award_for_study_time_increments_points_at_milestone()
    {
        $user = User::factory()->create(['points' => 0]);
        $service = new PointService();

        // Milestone 10 hours should award 20 points
        $service->awardForStudyTime($user, 10.0);

        $user->refresh();
        $this->assertEquals(20, $user->points);
        
        // Verify PointLog entry
        $this->assertTrue(PointLog::where('user_id', $user->id)
            ->where('type', 'study_time_milestone')
            ->where('reference_id', 10)
            ->exists());
    }

    /**
     * Test that points are not awarded multiple times for the same milestone.
     */
    public function test_award_for_study_time_does_not_double_award()
    {
        $user = User::factory()->create(['points' => 0]);
        $service = new PointService();

        // First award
        $service->awardForStudyTime($user, 10.0);
        $this->assertEquals(20, $user->fresh()->points);

        // Second call for the same milestone (or slightly more but below next milestone)
        $service->awardForStudyTime($user, 15.0);
        
        $user->refresh();
        $this->assertEquals(20, $user->points);
        $this->assertEquals(1, PointLog::where('user_id', $user->id)->count());
    }

    /**
     * Test that multiple milestones can be awarded if reached.
     */
    public function test_multiple_milestones_awarded()
    {
        $user = User::factory()->create(['points' => 0]);
        $service = new PointService();

        // 40 hours should reach 10h (20pts), 20h (40pts), and 40h (60pts) = 120pts total
        $service->awardForStudyTime($user, 40.0);

        $user->refresh();
        $this->assertEquals(120, $user->points);
        $this->assertEquals(3, PointLog::where('user_id', $user->id)->count());
    }
}
