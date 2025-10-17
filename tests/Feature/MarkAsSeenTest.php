<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkAsSeenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_mark_post_as_seen()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->assertFalse($post->isSeenBy($user));

        $response = $this->actingAs($user)
            ->postJson(route('posts.mark-seen', $post->slug));

        $response->assertStatus(200)
            ->assertJson(['is_seen' => true]);

        $this->assertTrue($post->fresh()->isSeenBy($user));
    }

    public function test_guest_cannot_mark_post_as_seen()
    {
        $post = Post::factory()->create();

        $response = $this->postJson(route('posts.mark-seen', $post->slug));

        $response->assertStatus(401);
    }

    public function test_marking_already_seen_post_returns_true()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Mark as seen first time
        $post->markAsSeen($user);
        $this->assertTrue($post->isSeenBy($user));

        // Try to mark as seen again
        $response = $this->actingAs($user)
            ->postJson(route('posts.mark-seen', $post->slug));

        $response->assertStatus(200)
            ->assertJson(['is_seen' => true]);
    }

    public function test_home_page_includes_is_seen_status()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        // Mark post as seen
        $post->markAsSeen($user);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home/Index')
            ->has('posts.data', 1)
            ->where('posts.data.0.is_seen', true)
        );
    }

    public function test_post_show_includes_is_seen_status()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        // Mark post as seen
        $post->markAsSeen($user);

        $response = $this->actingAs($user)->get(route('posts.show', $post->slug));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->where('post.is_seen', true)
        );
    }

    public function test_different_users_can_mark_same_post_as_seen()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();

        // User 1 marks as seen
        $response1 = $this->actingAs($user1)
            ->postJson(route('posts.mark-seen', $post->slug));

        $response1->assertStatus(200);
        $this->assertTrue($post->fresh()->isSeenBy($user1));
        $this->assertFalse($post->fresh()->isSeenBy($user2));

        // User 2 marks as seen
        $response2 = $this->actingAs($user2)
            ->postJson(route('posts.mark-seen', $post->slug));

        $response2->assertStatus(200);
        $this->assertTrue($post->fresh()->isSeenBy($user1));
        $this->assertTrue($post->fresh()->isSeenBy($user2));
    }

    public function test_seen_relationship_is_properly_configured()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->assertFalse($post->isSeenBy($user));

        $post->seenBy()->attach($user->id);

        $this->assertTrue($post->fresh()->isSeenBy($user));
        $this->assertEquals(1, $post->seenBy()->count());
    }

    public function test_authenticated_user_can_mark_post_as_unseen()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // First mark as seen
        $post->markAsSeen($user);
        $this->assertTrue($post->isSeenBy($user));

        // Then mark as unseen
        $response = $this->actingAs($user)
            ->postJson(route('posts.mark-unseen', $post->slug));

        $response->assertStatus(200)
            ->assertJson(['is_seen' => false]);

        $this->assertFalse($post->fresh()->isSeenBy($user));
    }

    public function test_guest_cannot_mark_post_as_unseen()
    {
        $post = Post::factory()->create();

        $response = $this->postJson(route('posts.mark-unseen', $post->slug));

        $response->assertStatus(401);
    }

    public function test_marking_unseen_post_as_unseen_returns_false()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Ensure post is not seen
        $this->assertFalse($post->isSeenBy($user));

        // Try to mark as unseen
        $response = $this->actingAs($user)
            ->postJson(route('posts.mark-unseen', $post->slug));

        $response->assertStatus(200)
            ->assertJson(['is_seen' => false]);

        $this->assertFalse($post->fresh()->isSeenBy($user));
    }

    public function test_mark_as_seen_and_unseen_toggle_functionality()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Initially not seen
        $this->assertFalse($post->isSeenBy($user));

        // Mark as seen
        $response = $this->actingAs($user)
            ->postJson(route('posts.mark-seen', $post->slug));
        $response->assertJson(['is_seen' => true]);
        $this->assertTrue($post->fresh()->isSeenBy($user));

        // Mark as unseen
        $response = $this->actingAs($user)
            ->postJson(route('posts.mark-unseen', $post->slug));
        $response->assertJson(['is_seen' => false]);
        $this->assertFalse($post->fresh()->isSeenBy($user));

        // Mark as seen again
        $response = $this->actingAs($user)
            ->postJson(route('posts.mark-seen', $post->slug));
        $response->assertJson(['is_seen' => true]);
        $this->assertTrue($post->fresh()->isSeenBy($user));
    }
}
