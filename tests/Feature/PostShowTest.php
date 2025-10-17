<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function published_post_can_be_viewed(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->has('post')
            ->where('post.id', $post->id)
            ->where('post.title', $post->title)
            ->where('post.slug', $post->slug)
            ->where('post.content', $post->content)
            ->has('relatedPosts')
        );
    }

    #[Test]
    public function unpublished_post_returns_404(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::DRAFT,
            'published_at' => null,
        ]);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertNotFound();
    }

    #[Test]
    public function future_published_post_returns_404(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->addHour(),
        ]);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertNotFound();
    }

    #[Test]
    public function viewing_post_increments_view_count(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'views_count' => 5,
        ]);

        $this->get(route('posts.show', $post->slug));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'views_count' => 6,
        ]);
    }

    #[Test]
    public function post_data_is_properly_formatted_for_frontend(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'type' => PostType::VIDEO,
            'tags' => ['laravel', 'php', 'testing'],
            'categories' => ['tutorial', 'backend'],
            'duration' => 3660, // 1 hour 1 minute
        ]);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->where('post.type.value', 'video')
            ->where('post.type.label', $post->getTypeLabel())
            ->where('post.type.icon', $post->getTypeIcon())
            ->where('post.type.color', $post->getTypeColor())
            ->where('post.status.value', 'published')
            ->where('post.status.label', $post->getStatusLabel())
            ->where('post.status.color', $post->getStatusColor())
            ->where('post.tags', ['laravel', 'php', 'testing'])
            ->where('post.categories', ['tutorial', 'backend'])
            ->where('post.duration', '1:01:00')
        );
    }

    #[Test]
    public function related_posts_are_included_and_limited_to_same_type(): void
    {
        // Create main post
        $mainPost = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'type' => PostType::VIDEO,
        ]);

        // Create related posts of same type
        $relatedPosts = Post::factory()->count(5)->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subMinutes(30),
            'type' => PostType::VIDEO,
        ]);

        // Create posts of different type (should not be included)
        Post::factory()->count(3)->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subMinutes(15),
            'type' => PostType::POST,
        ]);

        $response = $this->get(route('posts.show', $mainPost->slug));

        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->has('relatedPosts', 3) // Should be limited to 3
            ->where('relatedPosts.0.type.value', 'video')
            ->where('relatedPosts.1.type.value', 'video')
            ->where('relatedPosts.2.type.value', 'video')
        );
    }

    #[Test]
    public function related_posts_exclude_current_post(): void
    {
        $mainPost = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'type' => PostType::POST,
        ]);

        // Create other posts of same type
        Post::factory()->count(2)->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subMinutes(30),
            'type' => PostType::POST,
        ]);

        $response = $this->get(route('posts.show', $mainPost->slug));

        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->has('relatedPosts', 2)
            ->where('relatedPosts', fn ($relatedPosts) => collect($relatedPosts)->every(fn ($post) => $post['id'] !== $mainPost->id)
            )
        );
    }

    #[Test]
    public function author_information_is_included(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'author_avatar' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->where('post.author.name', 'John Doe')
            ->where('post.author.email', 'john@example.com')
            ->where('post.author.avatar', 'https://example.com/avatar.jpg')
        );
    }

    #[Test]
    public function formatted_dates_are_provided(): void
    {
        $publishedAt = now()->subDays(2);
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => $publishedAt,
        ]);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->where('post.published_at', $publishedAt->diffForHumans())
            ->where('post.formatted_published_at', $publishedAt->format('F j, Y'))
        );
    }

    #[Test]
    public function file_information_is_included_for_media_posts(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'type' => PostType::PODCAST,
            'file_url' => 'https://example.com/podcast.mp3',
            'file_size' => 1048576, // 1MB
            'file_type' => 'audio/mpeg',
        ]);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->where('post.file_url', 'https://example.com/podcast.mp3')
            ->where('post.file_size', '1 MB')
            ->where('post.file_type', 'audio/mpeg')
        );
    }

    #[Test]
    public function nonexistent_post_returns_404(): void
    {
        $response = $this->get(route('posts.show', 'nonexistent-slug'));

        $response->assertNotFound();
    }

    #[Test]
    public function post_with_no_related_posts_still_works(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'type' => PostType::VIDEO,
        ]);

        // No other posts exist

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Posts/Show')
            ->has('relatedPosts', 0)
        );
    }
}
