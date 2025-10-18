<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContentRankingService
{
    /**
     * Algorithm weights for content ranking
     */
    private const WEIGHTS = [
        'source_authority' => 0.35,
        'recency' => 0.3,
        'engagement' => 0.25,
        'source_diversity' => 0.1,
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
     * Get ranked posts with source diversity for anonymous users
     * This method uses pre-calculated ranking scores for efficiency
     */
    public function getRankedPostsForAnonymousUser(int $limit = 50): EloquentCollection
    {
        // Get top posts by ranking score (much more than needed for diversification)
        $posts = Post::published()
            ->orderByDesc('ranking_score')
            ->orderByDesc('published_at') // Tie-breaker for same scores
            ->limit($limit * 3) // Get 3x more to allow for diversity filtering
            ->get();

        return $this->applySourceDiversity($posts, $limit);
    }

    /**
     * Apply source diversity algorithm - max 2 consecutive posts from same source
     */
    public function applySourceDiversity(EloquentCollection $posts, int $limit): EloquentCollection
    {
        $result = new EloquentCollection;
        $sourceTracker = [];
        $consecutiveCount = [];

        foreach ($posts as $post) {
            if ($result->count() >= $limit) {
                break;
            }

            $source = $this->extractDomain($post->source_url ?: 'unknown');

            // Track consecutive posts from this source
            if (! isset($consecutiveCount[$source])) {
                $consecutiveCount[$source] = 0;
            }

            // Check if we can add this post (max 2 consecutive from same source)
            $canAdd = true;

            if ($result->count() >= 2) {
                $lastTwoPosts = $result->slice(-2);
                $lastTwoSources = $lastTwoPosts->map(function ($p) {
                    return $this->extractDomain($p->source_url ?: 'unknown');
                })->all();

                // If last 2 posts are from same source, don't add another from that source
                if (count(array_unique($lastTwoSources)) === 1 && count($lastTwoSources) > 0 && isset($lastTwoSources[0]) && $lastTwoSources[0] === $source) {
                    $canAdd = false;
                }
            }

            if ($canAdd) {
                $result->push($post);
                $consecutiveCount[$source]++;
            } else {
                // Store for potential later use if we have gaps
                $sourceTracker[$source][] = $post;
            }
        }

        // Fill remaining spots with posts from deferred sources if we haven't reached the limit
        if ($result->count() < $limit) {
            foreach ($sourceTracker as $source => $deferredPosts) {
                foreach ($deferredPosts as $post) {
                    if ($result->count() >= $limit) {
                        break 2;
                    }

                    // Check again if we can add it now
                    $lastTwoPosts = $result->slice(-2);
                    if ($lastTwoPosts->count() < 2) {
                        $result->push($post);

                        continue;
                    }

                    $lastTwoSources = $lastTwoPosts->map(function ($p) {
                        return $this->extractDomain($p->source_url ?: 'unknown');
                    })->all();

                    if (count(array_unique($lastTwoSources)) !== 1 || count($lastTwoSources) === 0 || ! isset($lastTwoSources[0]) || $lastTwoSources[0] !== $source) {
                        $result->push($post);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Legacy method for backward compatibility - now more efficient
     */
    public function rankForAnonymousUser(Collection $posts): Collection
    {
        if ($posts instanceof EloquentCollection) {
            // First sort by calculated score, then apply diversity
            $sortedPosts = $posts->sortByDesc(function (Post $post) {
                return $post->ranking_score ?: $this->calculateContentScore($post);
            });

            return $this->applySourceDiversity($sortedPosts, $posts->count());
        }

        // For regular collections, fall back to old method
        $rankedPosts = $posts->map(function (Post $post) {
            return [
                'post' => $post,
                'score' => $post->ranking_score ?: $this->calculateContentScore($post),
            ];
        })
            ->sortByDesc('score')
            ->pluck('post');

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
        $diversityScore = $this->getSourceDiversityScore($post);

        return
            $sourceScore * self::WEIGHTS['source_authority'] +
            $recencyScore * self::WEIGHTS['recency'] +
            $engagementScore * self::WEIGHTS['engagement'] +
            $diversityScore * self::WEIGHTS['source_diversity'];
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
        if (! $post->published_at) {
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
     * Calculate source diversity score to penalize over-represented sources
     */
    private function getSourceDiversityScore(Post $post): float
    {
        if (empty($post->source_url)) {
            return 5.0; // Neutral score for unknown sources
        }

        $domain = $this->extractDomain($post->source_url);
        
        // Cache the source distribution for performance
        $sourceStats = Cache::remember('source_distribution', 3600, function () {
            $totalPosts = Post::published()->count();
            if ($totalPosts === 0) {
                return [];
            }

            $sources = Post::published()
                ->selectRaw('source_url, COUNT(*) as count')
                ->whereNotNull('source_url')
                ->where('source_url', '!=', '')
                ->groupBy('source_url')
                ->get()
                ->mapWithKeys(function ($item) use ($totalPosts) {
                    $domain = $this->extractDomain($item->source_url);
                    return [$domain => [
                        'count' => $item->count,
                        'percentage' => ($item->count / $totalPosts) * 100
                    ]];
                })
                ->toArray();

            return $sources;
        });

        if (!isset($sourceStats[$domain])) {
            return 5.0; // Neutral score for new sources
        }

        $percentage = $sourceStats[$domain]['percentage'];

        // Apply logarithmic penalty for over-represented sources
        // Sources with >50% get heavily penalized, 20-50% get moderate penalty
        if ($percentage > 50) {
            return 2.0; // Heavy penalty for dominant sources (like stitcher.io)
        } elseif ($percentage > 20) {
            return 4.0; // Moderate penalty for over-represented sources
        } elseif ($percentage > 10) {
            return 6.0; // Slight penalty
        } elseif ($percentage > 5) {
            return 7.0; // Neutral
        } else {
            return 9.0; // Boost for under-represented sources
        }
    }

    /**
     * Get detailed score breakdown for debugging/analytics
     */
    public function getScoreBreakdown(Post $post): array
    {
        $sourceScore = $this->getSourceAuthorityScore($post);
        $recencyScore = $this->getRecencyScore($post);
        $engagementScore = $this->getEngagementScore($post);
        $diversityScore = $this->getSourceDiversityScore($post);

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
            'source_diversity' => [
                'score' => $diversityScore,
                'weight' => self::WEIGHTS['source_diversity'],
                'weighted_score' => $diversityScore * self::WEIGHTS['source_diversity'],
                'domain' => $this->extractDomain($post->source_url),
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
            return 'unknown';
        }

        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['host'])) {
            return 'unknown';
        }

        $host = $parsed['host'];

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
