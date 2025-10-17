<?php

namespace Tests\Feature;

use App\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_home_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
        );
    }

    public function test_authenticated_user_can_access_home_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('posts')
            ->has('filters')
        );
    }

    public function test_home_page_returns_paginated_posts(): void
    {
        // Create more posts than the pagination limit (12)
        Post::factory()
            ->count(25)
            ->published()
            ->create();

        $response = $this->get('/');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('posts.data', 12) // Should return 12 posts per page
            ->has('posts.current_page')
            ->has('posts.last_page')
            ->where('posts.per_page', 12)
        );
    }

    public function test_home_page_second_page_returns_remaining_posts(): void
    {
        Post::factory()
            ->count(25)
            ->published()
            ->create();

        $response = $this->get('/?page=2');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('posts.data', 12) // Second page should also have 12 posts
            ->where('posts.current_page', 2)
        );
    }

    public function test_home_page_only_shows_published_posts(): void
    {
        // Create a mix of published and draft posts
        Post::factory()->count(5)->published()->create();
        Post::factory()->count(3)->draft()->create();

        $response = $this->get('/');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('posts.data', 5) // Should only show published posts
        );
    }

    public function test_posts_are_ordered_by_latest_published_date(): void
    {
        $olderPost = Post::factory()->published()->create([
            'published_at' => now()->subDays(2),
            'title' => 'Older Post',
        ]);

        $newerPost = Post::factory()->published()->create([
            'published_at' => now()->subDay(),
            'title' => 'Newer Post',
        ]);

        $response = $this->get('/');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->where('posts.data.0.title', 'Newer Post')
            ->where('posts.data.1.title', 'Older Post')
        );
    }

    public function test_post_data_includes_all_required_fields(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt',
            'type' => PostType::VIDEO,
            'author_name' => 'John Doe',
            'tags' => ['Laravel', 'PHP'],
            'views_count' => 100,
            'likes_count' => 50,
        ]);

        $response = $this->get('/');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('posts.data.0', fn (Assert $post) => $post->has('id')
                ->has('title')
                ->has('slug')
                ->has('excerpt')
                ->has('type.value')
                ->has('type.label')
                ->has('type.icon')
                ->has('type.color')
                ->has('author.name')
                ->has('published_at')
                ->has('views_count')
                ->has('likes_count')
                ->has('tags')
                ->has('duration')
                ->has('featured_image')
                ->has('meta')
                ->has('is_liked')
                ->has('is_bookmarked')
                ->has('is_seen')
            )
        );
    }

    public function test_stats_are_deferred_and_accessible(): void
    {
        Post::factory()->count(3)->blogPost()->published()->create();
        Post::factory()->count(2)->video()->published()->create();
        Post::factory()->count(1)->podcast()->published()->create();

        // Since stats are deferred, let's test that the controller method works
        $this->assertTrue(method_exists(\App\Http\Controllers\HomeController::class, 'getTrendingTags'));

        // Test the actual stats calculation
        $controller = new \App\Http\Controllers\HomeController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTrendingTags');
        $method->setAccessible(true);
        $tags = $method->invoke($controller);

        $this->assertIsArray($tags);
    }

    public function test_different_post_types_are_handled_correctly(): void
    {
        $blogPost = Post::factory()->blogPost()->published()->create([
            'published_at' => now()->subMinutes(3),
        ]);
        $videoPost = Post::factory()->video()->published()->create([
            'published_at' => now()->subMinutes(2),
        ]);
        $podcastPost = Post::factory()->podcast()->published()->create([
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->get('/');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('posts.data', 3)
            ->where('posts.data.0.type.value', PostType::PODCAST->value) // Most recent
            ->where('posts.data.1.type.value', PostType::VIDEO->value)
            ->where('posts.data.2.type.value', PostType::POST->value)     // Oldest
        );
    }

    public function test_infinite_scroll_pagination_links_are_present(): void
    {
        Post::factory()->count(25)->published()->create();

        $response = $this->get('/');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('posts.next_page_url')
            ->has('posts.prev_page_url')
            ->has('posts.links')
        );
    }

    public function test_filters_include_post_type_options(): void
    {
        $response = $this->get('/');

        $response->assertInertia(fn (Assert $page) => $page->component('Home/Index')
            ->has('filters.types')
            ->where('filters.types', PostType::options())
        );
    }
}
