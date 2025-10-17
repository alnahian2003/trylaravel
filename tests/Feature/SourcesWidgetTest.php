<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourcesWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_sources_widget_data()
    {
        $user = User::factory()->create();

        // Create some sources for this user
        $sources = Source::factory()->count(5)->create([
            'user_id' => $user->id,
            'posts_count' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home/Index')
            ->has('userSources')
        );
    }

    public function test_guest_does_not_see_sources_widget_data()
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home/Index')
            ->where('userSources', null)
        );
    }

    public function test_sources_widget_shows_top_10_sources_ordered_by_posts_count()
    {
        $user = User::factory()->create();

        // Create 15 sources with different post counts
        $sources = [];
        for ($i = 0; $i < 15; $i++) {
            $sources[] = Source::factory()->create([
                'user_id' => $user->id,
                'posts_count' => 100 - $i, // Descending order
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home/Index')
            ->has('userSources', 10) // Should only return top 10
            ->where('userSources.0.posts_count', 100) // First should have highest count
            ->where('userSources.9.posts_count', 91) // 10th should be the 10th highest
        );
    }

    public function test_sources_widget_prioritizes_active_sources()
    {
        $user = User::factory()->create();

        // Create inactive source with high post count
        Source::factory()->create([
            'user_id' => $user->id,
            'posts_count' => 200,
            'is_active' => false,
        ]);

        // Create active source with lower post count
        $activeSource = Source::factory()->create([
            'user_id' => $user->id,
            'posts_count' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home/Index')
            ->has('userSources', 2)
            ->where('userSources.0.is_active', true) // Active source should come first
        );
    }

    public function test_user_only_sees_their_own_sources()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1Source = Source::factory()->create(['user_id' => $user1->id]);
        $user2Source = Source::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home/Index')
            ->has('userSources', 1)
            ->where('userSources.0.id', $user1Source->id)
        );
    }
}
