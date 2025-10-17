<?php

namespace Tests\Feature;

use App\Enums\ReportType;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportPostTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_report_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => ReportType::Spam->value,
                'description' => 'This post contains spam content.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Report submitted successfully. Thank you for helping keep our community safe.',
            ]);

        $this->assertDatabaseHas('reports', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'type' => ReportType::Spam->value,
            'description' => 'This post contains spam content.',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function guest_cannot_report_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->postJson(route('posts.report', $post), [
            'type' => ReportType::Spam->value,
            'description' => 'This post contains spam content.',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function report_requires_valid_type(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => 'invalid_type',
                'description' => 'This post contains spam content.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function report_requires_type(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'description' => 'This post contains spam content.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function report_description_is_optional(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => ReportType::Inappropriate->value,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'type' => ReportType::Inappropriate->value,
            'description' => null,
        ]);
    }

    #[Test]
    public function user_cannot_report_same_post_twice_for_same_reason(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        // First report
        $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => ReportType::Spam->value,
                'description' => 'This post contains spam content.',
            ]);

        // Second report with same type
        $response = $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => ReportType::Spam->value,
                'description' => 'Still spam content.',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'You have already reported this post for this reason.',
            ]);

        $this->assertEquals(1, Report::count());
    }

    #[Test]
    public function user_can_report_same_post_for_different_reasons(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        // First report for spam
        $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => ReportType::Spam->value,
                'description' => 'This post contains spam content.',
            ]);

        // Second report for inappropriate content
        $response = $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => ReportType::Inappropriate->value,
                'description' => 'This post also has inappropriate content.',
            ]);

        $response->assertStatus(200);

        $this->assertEquals(2, Report::count());
    }

    #[Test]
    public function description_cannot_exceed_maximum_length(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $longDescription = str_repeat('a', 1001); // 1001 characters

        $response = $this->actingAs($user)
            ->postJson(route('posts.report', $post), [
                'type' => ReportType::Other->value,
                'description' => $longDescription,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }
}
