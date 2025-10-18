<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ContentRankingService;
use App\Models\Post;
use App\Enums\PostStatus;
use App\Enums\PostType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ContentRankingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentRankingService $rankingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rankingService = new ContentRankingService();
    }

    public function test_it_ranks_posts_by_source_authority()
    {
        // Create posts from different source authorities
        $highAuthorityPost = Post::factory()->create([
            'source_url' => 'https://laravel.com/news/example',
            'published_at' => now()->subHour(),
            'views_count' => 100,
            'likes_count' => 10,
            'status' => PostStatus::PUBLISHED,
        ]);

        $lowAuthorityPost = Post::factory()->create([
            'source_url' => 'https://unknown-blog.com/post',
            'published_at' => now()->subHour(),
            'views_count' => 100,
            'likes_count' => 10,
            'status' => PostStatus::PUBLISHED,
        ]);

        $posts = new \Illuminate\Database\Eloquent\Collection([$highAuthorityPost, $lowAuthorityPost]);
        $rankedPosts = $this->rankingService->rankForAnonymousUser($posts);

        // High authority post should rank higher
        $this->assertEquals($highAuthorityPost->id, $rankedPosts->first()->id);
    }

    public function test_it_favors_recent_content()
    {
        // Create posts with different publish times
        $recentPost = Post::factory()->create([
            'source_url' => 'https://example.com/recent',
            'published_at' => now()->subHour(),
            'views_count' => 50,
            'likes_count' => 5,
            'status' => PostStatus::PUBLISHED,
        ]);

        $oldPost = Post::factory()->create([
            'source_url' => 'https://example.com/old',
            'published_at' => now()->subDays(30),
            'views_count' => 50,
            'likes_count' => 5,
            'status' => PostStatus::PUBLISHED,
        ]);

        $posts = new \Illuminate\Database\Eloquent\Collection([$oldPost, $recentPost]);
        $rankedPosts = $this->rankingService->rankForAnonymousUser($posts);

        // Recent post should rank higher
        $this->assertEquals($recentPost->id, $rankedPosts->first()->id);
    }

    public function test_it_considers_engagement_metrics()
    {
        // Create posts with different engagement levels
        $highEngagementPost = Post::factory()->create([
            'source_url' => 'https://example.com/popular',
            'published_at' => now()->subDay(),
            'views_count' => 1000,
            'likes_count' => 100,
            'status' => PostStatus::PUBLISHED,
        ]);

        $lowEngagementPost = Post::factory()->create([
            'source_url' => 'https://example.com/unpopular',
            'published_at' => now()->subDay(),
            'views_count' => 10,
            'likes_count' => 1,
            'status' => PostStatus::PUBLISHED,
        ]);

        $posts = new \Illuminate\Database\Eloquent\Collection([$lowEngagementPost, $highEngagementPost]);
        $rankedPosts = $this->rankingService->rankForAnonymousUser($posts);

        // High engagement post should rank higher
        $this->assertEquals($highEngagementPost->id, $rankedPosts->first()->id);
    }

    public function test_it_calculates_content_score_correctly()
    {
        $post = Post::factory()->create([
            'source_url' => 'https://laravel.com/news/example',
            'published_at' => now()->subHour(),
            'views_count' => 100,
            'likes_count' => 10,
            'status' => PostStatus::PUBLISHED,
        ]);

        $score = $this->rankingService->calculateContentScore($post);

        // Score should be a reasonable value
        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(10, $score);
    }

    public function test_it_provides_score_breakdown()
    {
        $post = Post::factory()->create([
            'source_url' => 'https://laravel.com/news/example',
            'published_at' => now()->subHour(),
            'views_count' => 100,
            'likes_count' => 10,
            'status' => PostStatus::PUBLISHED,
        ]);

        $breakdown = $this->rankingService->getScoreBreakdown($post);

        // Should contain all expected components
        $this->assertArrayHasKey('source_authority', $breakdown);
        $this->assertArrayHasKey('recency', $breakdown);
        $this->assertArrayHasKey('engagement', $breakdown);
        $this->assertArrayHasKey('total_score', $breakdown);

        // Each component should have score and weight
        $this->assertArrayHasKey('score', $breakdown['source_authority']);
        $this->assertArrayHasKey('weight', $breakdown['source_authority']);
        $this->assertArrayHasKey('weighted_score', $breakdown['source_authority']);
    }

    public function test_it_gets_trending_posts()
    {
        // Create some posts with recent high engagement
        Post::factory()->count(5)->create([
            'published_at' => now()->subHours(2),
            'views_count' => 50,
            'likes_count' => 5,
            'status' => PostStatus::PUBLISHED,
        ]);

        $trendingPosts = $this->rankingService->getTrendingPosts(3);

        $this->assertLessThanOrEqual(3, $trendingPosts->count());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $trendingPosts);
    }

    public function test_it_gets_hero_content()
    {
        // Create high-quality posts
        Post::factory()->count(3)->create([
            'source_url' => 'https://laravel.com/news/example',
            'published_at' => now()->subDays(2),
            'views_count' => 200,
            'likes_count' => 20,
            'status' => PostStatus::PUBLISHED,
        ]);

        $heroContent = $this->rankingService->getHeroContent(2);

        $this->assertLessThanOrEqual(2, $heroContent->count());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $heroContent);
    }

    public function test_it_handles_posts_without_source_url()
    {
        $post = Post::factory()->create([
            'source_url' => null,
            'published_at' => now()->subHour(),
            'views_count' => 100,
            'likes_count' => 10,
            'status' => PostStatus::PUBLISHED,
        ]);

        $score = $this->rankingService->calculateContentScore($post);

        // Should not throw error and return a valid score
        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
    }
}
