<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourcesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_sources_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('sources.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Sources/Index')
            ->has('sources')
            ->has('stats')
        );
    }

    public function test_guest_cannot_view_sources_page()
    {
        $response = $this->get(route('sources.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_sources_page_displays_user_sources()
    {
        $user = User::factory()->create();
        $source = Source::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('sources.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Sources/Index')
            ->has('sources', 1)
            ->where('sources.0.id', $source->id)
        );
    }

    public function test_sources_page_displays_correct_stats()
    {
        $user = User::factory()->create();
        Source::factory()->create(['user_id' => $user->id, 'is_active' => true, 'posts_count' => 10]);
        Source::factory()->create(['user_id' => $user->id, 'is_active' => false, 'posts_count' => 5]);

        $response = $this->actingAs($user)->get(route('sources.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Sources/Index')
            ->where('stats.active_sources', 1)
            ->where('stats.total_articles', 15)
        );
    }

    public function test_user_only_sees_their_own_sources()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1Source = Source::factory()->create(['user_id' => $user1->id]);
        $user2Source = Source::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->get(route('sources.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Sources/Index')
            ->has('sources', 1)
            ->where('sources.0.id', $user1Source->id)
        );
    }
}
