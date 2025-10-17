<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_source(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('sources.store'), [
            'url' => 'https://laravel-news.com/feed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Source added successfully!');

        $this->assertDatabaseHas('sources', [
            'user_id' => $user->id,
            'feed_url' => 'https://laravel-news.com/feed',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_add_source(): void
    {
        $response = $this->post(route('sources.store'), [
            'url' => 'https://laravel-news.com/feed',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_source_url_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('sources.store'), [
            'url' => '',
        ]);

        $response->assertSessionHasErrors(['url']);
    }

    public function test_source_url_must_be_valid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('sources.store'), [
            'url' => 'not-a-valid-url',
        ]);

        $response->assertSessionHasErrors(['url']);
    }

    public function test_user_cannot_add_duplicate_source(): void
    {
        $user = User::factory()->create();

        Source::factory()->create([
            'user_id' => $user->id,
            'feed_url' => 'https://laravel-news.com/feed',
        ]);

        $response = $this->actingAs($user)->post(route('sources.store'), [
            'url' => 'https://laravel-news.com/feed',
        ]);

        $response->assertSessionHasErrors(['url']);
        $this->assertEquals(1, Source::where('user_id', $user->id)->count());
    }

    public function test_different_users_can_add_same_source(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Source::factory()->create([
            'user_id' => $user1->id,
            'feed_url' => 'https://laravel-news.com/feed',
        ]);

        $response = $this->actingAs($user2)->post(route('sources.store'), [
            'url' => 'https://laravel-news.com/feed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Source added successfully!');
        $this->assertEquals(2, Source::where('feed_url', 'https://laravel-news.com/feed')->count());
    }

    public function test_user_can_delete_their_own_source(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('sources.destroy', $source));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Source removed successfully!');
        $this->assertDatabaseMissing('sources', ['id' => $source->id]);
    }

    public function test_user_cannot_delete_other_users_source(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $source = Source::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->delete(route('sources.destroy', $source));

        $response->assertStatus(403);
        $this->assertDatabaseHas('sources', ['id' => $source->id]);
    }
}
