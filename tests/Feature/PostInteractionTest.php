<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostInteractionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_like_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create(['likes_count' => 0]);

        $response = $this->actingAs($user)
            ->postJson(route('posts.like', $post->slug));

        $response->assertStatus(200)
            ->assertJson([
                'is_liked' => true,
                'likes_count' => 1,
            ]);

        $this->assertDatabaseHas('post_likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_unlike_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create(['likes_count' => 0]);

        // First like the post
        $post->toggleLike($user);

        $response = $this->actingAs($user)
            ->postJson(route('posts.like', $post->slug));

        $response->assertStatus(200)
            ->assertJson([
                'is_liked' => false,
                'likes_count' => 0,
            ]);

        $this->assertDatabaseMissing('post_likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_cannot_like_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->postJson(route('posts.like', $post->slug));

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_bookmark_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($user)
            ->postJson(route('posts.bookmark', $post->slug));

        $response->assertStatus(200)
            ->assertJson([
                'is_bookmarked' => true,
            ]);

        $this->assertDatabaseHas('post_bookmarks', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_unbookmark_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        // First bookmark the post
        $post->toggleBookmark($user);

        $response = $this->actingAs($user)
            ->postJson(route('posts.bookmark', $post->slug));

        $response->assertStatus(200)
            ->assertJson([
                'is_bookmarked' => false,
            ]);

        $this->assertDatabaseMissing('post_bookmarks', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_cannot_bookmark_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->postJson(route('posts.bookmark', $post->slug));

        $response->assertStatus(401);
    }
}
