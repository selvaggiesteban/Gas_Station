<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Block;
use App\Models\Topic;
use App\Models\Subscription;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TopicProgressTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $course = Course::create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'slug' => 'test-course',
            'is_active' => true
        ]);

        $block = Block::create([
            'course_id' => $course->id,
            'title' => 'Test Block',
            'order' => 1
        ]);

        $this->topic = Topic::create([
            'block_id' => $block->id,
            'title' => 'Test Topic',
            'slug' => 'test-topic',
            'content' => 'Test Content',
            'order' => 1
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'course_id' => $course->id,
            'price' => 100,
            'status' => 'active'
        ]);

        Subscription::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function test_user_can_mark_study_phases()
    {
        $response = $this->actingAs($this->user)->post(route('temas.completar', $this->topic), [
            'phase' => 'phase_reading'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('topic_user', [
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'phase_reading' => true,
            'is_completed' => false
        ]);
    }

    public function test_topic_is_marked_completed_when_all_phases_are_done()
    {
        $phases = ['phase_reading', 'phase_underlining', 'phase_review', 'phase_fast_review'];

        foreach ($phases as $phase) {
            $this->actingAs($this->user)->post(route('temas.completar', $this->topic), [
                'phase' => $phase
            ]);
        }

        $this->assertDatabaseHas('topic_user', [
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'phase_reading' => true,
            'phase_underlining' => true,
            'phase_review' => true,
            'phase_fast_review' => true,
            'is_completed' => true
        ]);
    }
}
