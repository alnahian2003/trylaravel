<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ContentRankingService
{
    /**
     * Algorithm weights for content ranking
     */
    private const WEIGHTS = [
        'source_authority' => 0.4,
        'recency' => 0.3,
        'engagement' => 0.3,
    ];

    /**
     * Source authority scores based on domain reputation
     * Scale: 1-10 (10 being the highest authority)
     */
    private const SOURCE_AUTHORITY = [
        // Official Laravel sources
        'laravel.com' => 10,
        'blog.laravel.com' => 10,
        
        // High authority Laravel community sites
        'laracasts.com' => 9,
        'laravel-news.com' => 9,
        'codecourse.com' => 9,
        'laraveldaily.com' => 9,
        
        // Well-known Laravel developers
        'freek.dev' => 8,
        'mattstauffer.com' => 8,
        'stitcher.io' => 8,
        'christoph-rumpel.com' => 8,
        'dyrynda.com.au' => 8,
        
        // Popular Laravel community blogs
        'tighten.co' => 7,
        'spatie.be' => 7,
        'beyondco.de' => 7,
        'nunomaduro.com' => 7,
        
        // Medium authority sources
        'dev.to' => 6,
        'medium.com' => 6,
        'hackernoon.com' => 6,
        
        // Lower authority but still relevant
        'blog.*' => 5, // Generic blog subdomains
        '*.dev' => 5,  // .dev domains
        
        // Default for unknown sources
        'default' => 3,
    ];

    /**
     * Rank posts for anonymous users using the multi-factor algorithm
     */
    public function rankForAnonymousUser(Collection $posts): Collection
    {
        $rankedPosts = $posts->map(function (Post $post) {
            return [
                'post' => $post,
                'score' => $this->calculateContentScore($post),
                'score_breakdown' => $this->getScoreBreakdown($post),
            ];
        })
        ->sortByDesc('score')
        ->pluck('post');

        // Convert back to EloquentCollection if input was EloquentCollection
        if ($posts instanceof EloquentCollection) {
            return new EloquentCollection($rankedPosts->all());
        }

        return $rankedPosts;
    }

    /**
     * Calculate the overall content score for a post
     */
    public function calculateContentScore(Post $post): float
    {
        $sourceScore = $this->getSourceAuthorityScore($post);
        $recencyScore = $this->getRecencyScore($post);
        $engagementScore = $this->getEngagementScore($post);

        return (
            $sourceScore * self::WEIGHTS['source_authority'] +
            $recencyScore * self::WEIGHTS['recency'] +
            $engagementScore * self::WEIGHTS['engagement']
        );
    }

    /**
     * Get source authority score based on the post's source URL
     */
    private function getSourceAuthorityScore(Post $post): float
    {
        if (empty($post->source_url)) {
            return self::SOURCE_AUTHORITY['default'];
        }

        $domain = $this->extractDomain($post->source_url);
        
        // Check for exact domain match
        if (isset(self::SOURCE_AUTHORITY[$domain])) {
            return (float) self::SOURCE_AUTHORITY[$domain];
        }

        // Check for pattern matches
        foreach (self::SOURCE_AUTHORITY as $pattern => $score) {
            if ($this->matchesDomainPattern($domain, $pattern)) {
                return (float) $score;
            }
        }

        return (float) self::SOURCE_AUTHORITY['default'];
    }

    /**
     * Calculate recency score with exponential decay
     */
    private function getRecencyScore(Post $post): float
    {
        if (!$post->published_at) {
            return 0.0;
        }

        $hoursOld = $post->published_at->diffInHours(now());
        
        // Exponential decay: newer content gets higher scores
        // Fresh content (< 24h) gets full score, then decays
        if ($hoursOld <= 24) {
            return 10.0; // Maximum score for very fresh content
        } elseif ($hoursOld <= 168) { // 1 week
            return 10.0 * exp(-($hoursOld - 24) / 168); // Slow decay in first week
        } else {
            return 3.0 * exp(-($hoursOld - 168) / (24 * 30)); // Faster decay after a week
        }
    }

    /**
     * Calculate engagement score based on views, likes, and interaction rates
     */
    private function getEngagementScore(Post $post): float
    {
        $hoursLive = max($post->published_at?->diffInHours(now()) ?? 1, 1);
        
        // Normalize engagement metrics by time
        $viewVelocity = $post->views_count / $hoursLive;
        $likeVelocity = $post->likes_count / $hoursLive;
        
        // Calculate engagement rate (likes per view)
        $engagementRate = $post->views_count > 0 
            ? ($post->likes_count / $post->views_count) * 100 
            : 0;

        // Weighted engagement score
        $rawScore = (
            $viewVelocity * 0.4 +
            $likeVelocity * 10 * 0.4 + // Weight likes more heavily
            $engagementRate * 0.2
        );

        // Scale to 0-10 range with logarithmic scaling for large numbers
        return min(10.0, log($rawScore + 1) * 2);
    }

    /**
     * Get detailed score breakdown for debugging/analytics
     */
    public function getScoreBreakdown(Post $post): array
    {
        $sourceScore = $this->getSourceAuthorityScore($post);
        $recencyScore = $this->getRecencyScore($post);
        $engagementScore = $this->getEngagementScore($post);

        return [
            'source_authority' => [
                'score' => $sourceScore,
                'weight' => self::WEIGHTS['source_authority'],
                'weighted_score' => $sourceScore * self::WEIGHTS['source_authority'],
                'domain' => $this->extractDomain($post->source_url),
            ],
            'recency' => [
                'score' => $recencyScore,
                'weight' => self::WEIGHTS['recency'],
                'weighted_score' => $recencyScore * self::WEIGHTS['recency'],
                'hours_old' => $post->published_at?->diffInHours(now()),
            ],
            'engagement' => [
                'score' => $engagementScore,
                'weight' => self::WEIGHTS['engagement'],
                'weighted_score' => $engagementScore * self::WEIGHTS['engagement'],
                'views' => $post->views_count,
                'likes' => $post->likes_count,
                'engagement_rate' => $post->views_count > 0 
                    ? round(($post->likes_count / $post->views_count) * 100, 2) 
                    : 0,
            ],
            'total_score' => $this->calculateContentScore($post),
        ];
    }

    /**
     * Get trending posts with boost for high engagement velocity
     */
    public function getTrendingPosts(int $limit = 10, int $hours = 24): EloquentCollection
    {
        return Cache::remember("trending_posts_{$limit}_{$hours}", 300, function () use ($limit, $hours) {
            $posts = Post::published()
                ->where('published_at', '>=', now()->subHours($hours))
                ->get();

            return $this->rankForAnonymousUser($posts)
                ->filter(function (Post $post) {
                    // Only include posts with significant engagement
                    return $post->views_count >= 10 || $post->likes_count >= 2;
                })
                ->take($limit);
        });
    }

    /**
     * Get high-quality posts for hero content
     */
    public function getHeroContent(int $limit = 3): EloquentCollection
    {
        return Cache::remember("hero_content_{$limit}", 600, function () use ($limit) {
            $posts = Post::published()
                ->where('published_at', '>=', now()->subDays(7)) // Last week
                ->get();

            return $this->rankForAnonymousUser($posts)
                ->filter(function (Post $post) {
                    $score = $this->calculateContentScore($post);
                    return $score >= 7.0; // High-quality threshold
                })
                ->take($limit);
        });
    }

    /**
     * Extract domain from URL
     */
    private function extractDomain(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        
        // Remove www. prefix
        return preg_replace('/^www\./', '', strtolower($host));
    }

    /**
     * Check if domain matches a pattern (supports wildcards)
     */
    private function matchesDomainPattern(string $domain, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(
            ['*', '.'],
            ['.*', '\.'],
            $pattern
        );
        
        return preg_match("/^{$regex}$/", $domain) === 1;
    }

    /**
     * Get algorithm configuration for debugging/admin
     */
    public function getConfiguration(): array
    {
        return [
            'weights' => self::WEIGHTS,
            'source_authorities' => self::SOURCE_AUTHORITY,
            'version' => '1.0.0',
            'last_updated' => now()->toISOString(),
        ];
    }
}
