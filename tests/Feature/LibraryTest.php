<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_library(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('library'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Library/Index')
            ->has('bookmarks')
            ->has('categoryCounts')
            ->has('totalBookmarks')
        );
    }

    public function test_unauthenticated_user_cannot_access_library(): void
    {
        $response = $this->get(route('library'));

        $response->assertRedirect('/login');
    }

    public function test_library_shows_bookmarked_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create([
            'categories' => ['testing', 'performance'],
        ]);

        // Bookmark the post
        $post->bookmarks()->attach($user->id);

        $response = $this->actingAs($user)->get(route('library'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Library/Index')
            ->where('totalBookmarks', 1)
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.id', $post->id)
        );
    }

    public function test_library_filters_posts_by_category(): void
    {
        $user = User::factory()->create();

        // Create posts with different categories
        $tutorialPost = Post::factory()->published()->create([
            'categories' => ['Tutorial', 'PHP'],
        ]);

        $newsPost = Post::factory()->published()->create([
            'categories' => ['News', 'Laravel'],
        ]);

        // Bookmark both posts
        $tutorialPost->bookmarks()->attach($user->id);
        $newsPost->bookmarks()->attach($user->id);

        // Test filtering by Tutorial category
        $response = $this->actingAs($user)->get(route('library', ['category' => 'Tutorial']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Library/Index')
            ->where('selectedCategory', 'Tutorial')
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.id', $tutorialPost->id)
        );

        // Test filtering by News category
        $response = $this->actingAs($user)->get(route('library', ['category' => 'News']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Library/Index')
            ->where('selectedCategory', 'News')
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.id', $newsPost->id)
        );

        // Test no filter shows all posts
        $response = $this->actingAs($user)->get(route('library'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Library/Index')
            ->where('selectedCategory', null)
            ->has('bookmarks.data', 2)
        );
    }

    public function test_library_sorts_posts_correctly(): void
    {
        $user = User::factory()->create();

        // Create posts with different attributes for sorting
        $oldPost = Post::factory()->published()->create([
            'title' => 'A First Post',
            'views_count' => 100,
        ]);

        $newPost = Post::factory()->published()->create([
            'title' => 'Z Last Post',
            'views_count' => 500,
        ]);

        // Bookmark both posts (bookmark old post first)
        $oldPost->bookmarks()->attach($user->id, ['created_at' => now()->subDay()]);
        $newPost->bookmarks()->attach($user->id, ['created_at' => now()]);

        // Test newest first (default)
        $response = $this->actingAs($user)->get(route('library'));
        $response->assertInertia(fn ($page) => $page
            ->where('selectedSort', 'newest')
            ->where('bookmarks.data.0.id', $newPost->id)
            ->where('bookmarks.data.1.id', $oldPost->id)
        );

        // Test oldest first
        $response = $this->actingAs($user)->get(route('library', ['sort' => 'oldest']));
        $response->assertInertia(fn ($page) => $page
            ->where('selectedSort', 'oldest')
            ->where('bookmarks.data.0.id', $oldPost->id)
            ->where('bookmarks.data.1.id', $newPost->id)
        );

        // Test most read
        $response = $this->actingAs($user)->get(route('library', ['sort' => 'most_read']));
        $response->assertInertia(fn ($page) => $page
            ->where('selectedSort', 'most_read')
            ->where('bookmarks.data.0.id', $newPost->id)
            ->where('bookmarks.data.1.id', $oldPost->id)
        );

        // Test alphabetical
        $response = $this->actingAs($user)->get(route('library', ['sort' => 'alphabetical']));
        $response->assertInertia(fn ($page) => $page
            ->where('selectedSort', 'alphabetical')
            ->where('bookmarks.data.0.id', $oldPost->id)
            ->where('bookmarks.data.1.id', $newPost->id)
        );
    }
}
