<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Block;
use App\Models\Topic;
use App\Models\Subscription;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a user WITHOUT an active subscription is redirected or gets 403.
     */
    public function test_user_without_subscription_is_redirected_from_restricted_topic()
    {
        // Setup: Create user, course, block and topic
        $user = User::factory()->create();
        
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

        $topic = Topic::create([
            'block_id' => $block->id,
            'title' => 'Test Topic',
            'slug' => 'test-topic',
            'content' => 'Test Content',
            'order' => 1
        ]);

        // Action: Try to access the topic
        $response = $this->actingAs($user)->get(route('temas.ver', ['topic' => $topic->slug]));

        // Assertion: Redirected to subscription index (as per middleware logic)
        $response->assertRedirect(route('subscription.index'));
    }

    /**
     * Test that a user WITH an active subscription can access the topic.
     */
    public function test_user_with_subscription_can_access_topic()
    {
        // Setup: Create user, course, block, topic, product and active subscription
        $user = User::factory()->create();
        
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

        $topic = Topic::create([
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
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        // Action: Try to access the topic
        $response = $this->actingAs($user)->get(route('temas.ver', ['topic' => $topic->slug]));

        // Assertion: Success
        $response->assertStatus(200);
    }
}
