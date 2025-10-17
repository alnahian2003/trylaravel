# LaravelSense Content Recommendation Algorithm

## Overview

This document outlines a comprehensive content recommendation system designed specifically for **LaravelSense**, a Laravel-focused content aggregation platform. The algorithm uses a hybrid approach combining user preferences, content quality metrics, and behavioral learning to deliver personalized content recommendations.

## Algorithm Architecture

### Core Formula

```
Content Score = (0.4 × User_Interest_Score) + 
                (0.25 × Content_Quality_Score) + 
                (0.2 × Recency_Score) + 
                (0.1 × Social_Engagement_Score) + 
                (0.05 × Diversity_Score)
```

### Scoring Components Breakdown

| Component | Weight | Purpose |
|-----------|--------|---------|
| User Interest Score | 40% | Matches content topics with user preferences |
| Content Quality Score | 25% | Evaluates source credibility and content depth |
| Recency Score | 20% | Prioritizes fresh, relevant content |
| Social Engagement Score | 10% | Considers community interaction (likes, bookmarks) |
| Diversity Score | 5% | Ensures variety in content sources and topics |

## Database Schema

### Core Tables

```sql
-- Articles table
CREATE TABLE articles (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    author VARCHAR(255),
    source VARCHAR(255),
    topics JSON, -- ['testing', 'performance', 'apis']
    difficulty_level ENUM('beginner', 'intermediate', 'advanced'),
    reading_time INT, -- minutes
    likes_count INT DEFAULT 0,
    bookmarks_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- User interests tracking
CREATE TABLE user_interests (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    topic VARCHAR(255), -- testing, performance, security, etc.
    weight INT DEFAULT 1, -- 1-10 scale
    engagement_count INT DEFAULT 0,
    last_engaged_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User-article interaction tracking
CREATE TABLE user_article_interactions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    article_id BIGINT,
    liked BOOLEAN DEFAULT FALSE,
    bookmarked BOOLEAN DEFAULT FALSE,
    seen_at TIMESTAMP, -- automatically tracked when article appears in feed
    clicked_at TIMESTAMP, -- when user clicks on article
    reading_time INT, -- actual time spent reading
    read_at TIMESTAMP, -- when user actually reads the content
    scroll_percentage FLOAT DEFAULT 0, -- how much of article user scrolled through
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (article_id) REFERENCES articles(id),
    INDEX idx_user_seen (user_id, seen_at),
    INDEX idx_user_article_interactions (user_id, article_id)
);
```

## Laravel Implementation

### 1. Models

```php
<?php
// app/Models/Article.php
class Article extends Model
{
    protected $fillable = [
        'title', 'content', 'author', 'source', 'topics', 
        'difficulty_level', 'reading_time', 'likes_count', 'bookmarks_count'
    ];

    protected $casts = [
        'topics' => 'array',
    ];

    public function interactions()
    {
        return $this->hasMany(UserArticleInteraction::class);
    }

    /**
     * Check how many topics this article shares with user's interests
     */
    public function matchesUserInterests(User $user): int
    {
        $userTopics = $user->interests->pluck('topic')->toArray();
        $articleTopics = $this->topics;
        
        return count(array_intersect($userTopics, $articleTopics));
    }
}

// app/Models/UserArticleInteraction.php
class UserArticleInteraction extends Model
{
    protected $fillable = [
        'user_id', 'article_id', 'liked', 'bookmarked', 
        'seen_at', 'clicked_at', 'read_at', 'reading_time', 'scroll_percentage'
    ];

    protected $casts = [
        'seen_at' => 'datetime',
        'clicked_at' => 'datetime',
        'read_at' => 'datetime',
        'liked' => 'boolean',
        'bookmarked' => 'boolean',
        'scroll_percentage' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Mark article as seen (automatically tracked)
     */
    public function markAsSeen()
    {
        if (!$this->seen_at) {
            $this->seen_at = now();
            $this->save();
        }
    }

    /**
     * Track user click on article
     */
    public function trackClick()
    {
        $this->clicked_at = now();
        if (!$this->seen_at) {
            $this->seen_at = now();
        }
        $this->save();
    }

    /**
     * Check if user has meaningful engagement with article
     */
    public function hasEngagement(): bool
    {
        return $this->liked || 
               $this->bookmarked || 
               $this->read_at !== null || 
               $this->scroll_percentage > 0.3; // 30% scroll threshold
    }

    /**
     * Calculate engagement score for this interaction
     */
    public function getEngagementScore(): float
    {
        $score = 0;
        
        if ($this->seen_at) $score += 0.1;
        if ($this->clicked_at) $score += 0.3;
        if ($this->scroll_percentage > 0.5) $score += 0.4;
        if ($this->read_at) $score += 0.5;
        if ($this->liked) $score += 0.7;
        if ($this->bookmarked) $score += 1.0;
        
        return min(1.0, $score);
    }
}

// app/Models/UserInterest.php
class UserInterest extends Model
{
    protected $fillable = ['user_id', 'topic', 'weight', 'engagement_count', 'last_engaged_at'];

    protected $casts = [
        'last_engaged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increase user engagement with this topic
     * Called when user reads, likes, or bookmarks related content
     */
    public function increaseEngagement()
    {
        $this->increment('engagement_count');
        $this->weight = min(10, $this->weight + 0.1); // Cap at 10
        $this->last_engaged_at = now();
        $this->save();
    }

    /**
     * Decrease interest over time if not engaged
     */
    public function decayInterest()
    {
        if ($this->last_engaged_at && $this->last_engaged_at->isAfter(now()->subDays(30))) {
            $this->weight = max(1, $this->weight - 0.05);
            $this->save();
        }
    }
}

// Add to app/Models/User.php
class User extends Authenticatable
{
    public function interests()
    {
        return $this->hasMany(UserInterest::class);
    }

    public function articleInteractions()
    {
        return $this->hasMany(UserArticleInteraction::class);
    }

    public function getTopInterests($limit = 5)
    {
        return $this->interests()
            ->orderByDesc('weight')
            ->orderByDesc('engagement_count')
            ->limit($limit)
            ->get();
    }
}
```

### 2. Core Recommendation Service

```php
<?php
// app/Services/ContentRecommendationService.php

class ContentRecommendationService
{
    /**
     * Get personalized article recommendations for a user
     */
    public function getRecommendationsForUser(User $user, int $limit = 10): Collection
    {
        // Get articles user has already seen or interacted with
        $excludedArticleIds = $this->getExcludedArticleIds($user);

        // Get candidate articles (recent content only)
        $articles = Article::whereNotIn('id', $excludedArticleIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        // Score each article for this specific user
        $scoredArticles = $articles->map(function ($article) use ($user) {
            return [
                'article' => $article,
                'score' => $this->calculateArticleScore($article, $user)
            ];
        });

        // Sort by score and return top recommendations
        $recommendations = $scoredArticles
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('article');

        // Track that user has seen these recommendations
        $this->trackSeenRecommendations($user, $recommendations);

        return $recommendations;
    }

    /**
     * Get article IDs that should be excluded from recommendations
     */
    private function getExcludedArticleIds(User $user): array
    {
        return $user->articleInteractions()
            ->where(function ($query) {
                $query->whereNotNull('seen_at') // User has seen it
                      ->orWhereNotNull('read_at') // User has read it
                      ->orWhereNotNull('clicked_at') // User has clicked it
                      ->orWhere('liked', true) // User has liked it
                      ->orWhere('bookmarked', true); // User has bookmarked it
            })
            ->pluck('article_id')
            ->toArray();
    }

    /**
     * Track that user has seen these recommendations
     */
    private function trackSeenRecommendations(User $user, Collection $articles): void
    {
        foreach ($articles as $article) {
            UserArticleInteraction::updateOrCreate(
                ['user_id' => $user->id, 'article_id' => $article->id],
                ['seen_at' => now()]
            );
        }
    }

    /**
     * Calculate comprehensive score for an article relative to a user
     */
    private function calculateArticleScore(Article $article, User $user): float
    {
        $score = 0;

        // 1. User Interest Matching (40% weight)
        $interestScore = $this->calculateInterestScore($article, $user);
        $score += $interestScore * 0.4;

        // 2. Content Quality Assessment (25% weight)
        $qualityScore = $this->calculateQualityScore($article);
        $score += $qualityScore * 0.25;

        // 3. Content Recency (20% weight)
        $recencyScore = $this->calculateRecencyScore($article);
        $score += $recencyScore * 0.2;

        // 4. Social Engagement (10% weight)
        $engagementScore = $this->calculateEngagementScore($article);
        $score += $engagementScore * 0.1;

        // 5. Content Diversity (5% weight)
        $diversityScore = $this->calculateDiversityScore($article, $user);
        $score += $diversityScore * 0.05;

        return round($score, 2);
    }

    /**
     * Score based on how well article topics match user interests
     */
    private function calculateInterestScore(Article $article, User $user): float
    {
        $userInterests = $user->interests->keyBy('topic');
        $score = 0;
        $maxPossibleScore = 0;

        foreach ($article->topics as $topic) {
            $maxPossibleScore += 10; // Max weight is 10

            if ($userInterests->has($topic)) {
                $interest = $userInterests[$topic];
                
                // Base score from user's interest weight (1-10)
                $topicScore = $interest->weight;
                
                // Recent engagement boost (20% bonus)
                if ($interest->last_engaged_at && 
                    $interest->last_engaged_at->isAfter(now()->subDays(7))) {
                    $topicScore *= 1.2;
                }
                
                // Long-term engagement boost
                $engagementBoost = min(2, $interest->engagement_count / 10);
                $topicScore += $engagementBoost;
                
                $score += $topicScore;
            }
        }

        // Normalize to 0-1 scale
        return $maxPossibleScore > 0 ? min(1, $score / $maxPossibleScore) : 0;
    }

    /**
     * Evaluate content quality based on source, length, and social proof
     */
    private function calculateQualityScore(Article $article): float
    {
        $score = 0;

        // Source reputation scoring
        $trustedSources = [
            'Laravel News' => 1.0,
            'freek.dev' => 0.9,
            'Laravel Daily' => 0.9,
            'Matt Stauffer' => 0.8,
            'Spatie' => 0.8,
            'Christoph Rumpel' => 0.8,
            'Laravel.io' => 0.7,
        ];

        $sourceScore = $trustedSources[$article->source] ?? 0.5;
        $score += $sourceScore * 0.4;

        // Content depth scoring (optimal reading time: 5-15 minutes)
        if ($article->reading_time >= 5 && $article->reading_time <= 15) {
            $score += 0.3; // Sweet spot for comprehensive but digestible content
        } elseif ($article->reading_time > 15) {
            $score += 0.2; // Still valuable but requires more time investment
        } else {
            $score += 0.1; // Short articles might lack depth
        }

        // Social proof scoring (likes + bookmarks)
        $socialProof = $article->likes_count + $article->bookmarks_count;
        $socialScore = min(0.3, $socialProof / 100); // Normalize to max 0.3
        $score += $socialScore;

        return min(1, $score);
    }

    /**
     * Score content based on how recent it is
     */
    private function calculateRecencyScore(Article $article): float
    {
        $daysOld = $article->created_at->diffInDays(now());
        
        if ($daysOld <= 1) return 1.0;      // Brand new content
        if ($daysOld <= 7) return 0.8;      // This week
        if ($daysOld <= 30) return 0.6;     // This month
        if ($daysOld <= 90) return 0.4;     // Last quarter
        
        return 0.2; // Older content (still has some value)
    }

    /**
     * Score based on community engagement relative to article age
     */
    private function calculateEngagementScore(Article $article): float
    {
        $totalEngagement = $article->likes_count + $article->bookmarks_count;
        
        // Normalize engagement by article age to account for time bias
        $daysOld = max(1, $article->created_at->diffInDays(now()));
        $engagementRate = $totalEngagement / $daysOld;
        
        return min(1, $engagementRate / 10);
    }

    /**
     * Encourage diversity by reducing scores for overrepresented sources
     */
    private function calculateDiversityScore(Article $article, User $user): float
    {
        // Count recent reads from this source
        $recentReadsFromSource = $user->articleInteractions()
            ->whereHas('article', function ($query) use ($article) {
                $query->where('source', $article->source);
            })
            ->where('read_at', '>=', now()->subDays(7))
            ->count();

        // Apply diversity penalty for overrepresented sources
        if ($recentReadsFromSource >= 3) return 0.2; // Heavy penalty
        if ($recentReadsFromSource >= 2) return 0.5; // Medium penalty
        if ($recentReadsFromSource >= 1) return 0.8; // Light penalty
        
        return 1.0; // No penalty for diverse sources
    }
}
```

### 3. Controller Implementation

```php
<?php
// app/Http/Controllers/FeedController.php

class FeedController extends Controller
{
    private ContentRecommendationService $recommendationService;

    public function __construct(ContentRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Display personalized feed
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        if ($user) {
            // Get personalized recommendations
            $articles = $this->recommendationService->getRecommendationsForUser($user, 20);
        } else {
            // Fallback to popular content for guests
            $articles = Article::orderByDesc('likes_count')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        }

        return view('feed', compact('articles'));
    }

    /**
     * Track when user clicks on an article
     */
    public function trackClick(Request $request, Article $article)
    {
        if (!auth()->check()) {
            return response()->json(['status' => 'guest']);
        }

        $user = auth()->user();
        
        // Record the click interaction
        $interaction = UserArticleInteraction::updateOrCreate(
            ['user_id' => $user->id, 'article_id' => $article->id],
            []
        );
        
        $interaction->trackClick();

        return response()->json(['status' => 'success']);
    }

    /**
     * Track reading progress and engagement
     */
    public function trackReading(Request $request, Article $article)
    {
        if (!auth()->check()) {
            return response()->json(['status' => 'guest']);
        }

        $user = auth()->user();
        
        // Record the reading interaction
        UserArticleInteraction::updateOrCreate(
            ['user_id' => $user->id, 'article_id' => $article->id],
            [
                'read_at' => now(),
                'reading_time' => $request->input('reading_time', 0),
                'scroll_percentage' => $request->input('scroll_percentage', 0)
            ]
        );

        // Update user interests based on article topics
        foreach ($article->topics as $topic) {
            $interest = UserInterest::firstOrCreate(
                ['user_id' => $user->id, 'topic' => $topic],
                ['weight' => 1]
            );
            
            $interest->increaseEngagement();
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Track article impressions (when articles appear in feed)
     */
    public function trackImpressions(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['status' => 'guest']);
        }

        $user = auth()->user();
        $articleIds = $request->input('article_ids', []);

        foreach ($articleIds as $articleId) {
            $interaction = UserArticleInteraction::updateOrCreate(
                ['user_id' => $user->id, 'article_id' => $articleId],
                []
            );
            
            $interaction->markAsSeen();
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle bookmark action
     */
    public function bookmark(Article $article)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Record bookmark interaction
        $interaction = UserArticleInteraction::updateOrCreate(
            ['user_id' => $user->id, 'article_id' => $article->id],
            ['bookmarked' => true]
        );

        // Boost interest in article topics (bookmarking shows strong interest)
        foreach ($article->topics as $topic) {
            $interest = UserInterest::firstOrCreate(
                ['user_id' => $user->id, 'topic' => $topic],
                ['weight' => 1]
            );
            
            // Bookmarking gives bigger boost than just reading
            $interest->weight = min(10, $interest->weight + 0.5);
            $interest->save();
        }

        // Update article bookmark count
        $article->increment('bookmarks_count');

        return back()->with('success', 'Article bookmarked!');
    }
}
```

### 4. RSS Feed Processing

```php
<?php
// app/Console/Commands/ProcessRSSFeeds.php

class ProcessRSSFeeds extends Command
{
    protected $signature = 'feeds:process';
    protected $description = 'Process RSS feeds and extract Laravel content';

    public function handle()
    {
        $sources = [
            'Laravel News' => 'https://laravel-news.com/feed',
            'freek.dev' => 'https://freek.dev/feed',
            'Laravel Daily' => 'https://laraveldaily.com/feed',
            'Matt Stauffer' => 'https://mattstauffer.com/feed',
            'Spatie' => 'https://spatie.be/feed',
        ];

        foreach ($sources as $sourceName => $feedUrl) {
            $this->info("Processing {$sourceName}...");
            $this->processFeed($sourceName, $feedUrl);
        }

        $this->info('RSS feed processing completed!');
    }

    private function processFeed(string $sourceName, string $feedUrl)
    {
        try {
            $feed = \Feeds::make($feedUrl);
            $itemCount = 0;
            
            foreach ($feed->get_items() as $item) {
                $title = $item->get_title();
                $content = strip_tags($item->get_content());
                
                // Skip if article already exists
                if (Article::where('title', $title)->where('source', $sourceName)->exists()) {
                    continue;
                }
                
                // Extract topics and metadata
                $topics = $this->extractTopics($title . ' ' . $content);
                $difficulty = $this->assessDifficulty($content);
                $readingTime = $this->estimateReadingTime($content);
                
                Article::create([
                    'title' => $title,
                    'content' => $content,
                    'author' => $item->get_author()->get_name() ?? $sourceName,
                    'source' => $sourceName,
                    'topics' => $topics,
                    'difficulty_level' => $difficulty,
                    'reading_time' => $readingTime,
                    'created_at' => Carbon::parse($item->get_date()),
                ]);
                
                $itemCount++;
            }
            
            $this->info("Added {$itemCount} new articles from {$sourceName}");
            
        } catch (\Exception $e) {
            $this->error("Failed to process {$sourceName}: " . $e->getMessage());
        }
    }

    /**
     * Extract Laravel-related topics from content using keyword matching
     */
    private function extractTopics(string $text): array
    {
        $keywords = [
            'testing' => ['test', 'phpunit', 'pest', 'tdd', 'testing', 'mock', 'feature test'],
            'performance' => ['performance', 'optimize', 'cache', 'speed', 'slow', 'memory', 'query'],
            'security' => ['security', 'auth', 'login', 'csrf', 'xss', 'sql injection', 'sanctum'],
            'apis' => ['api', 'rest', 'graphql', 'endpoint', 'json', 'resource', 'controller'],
            'database' => ['database', 'eloquent', 'migration', 'query', 'mysql', 'postgresql'],
            'frontend' => ['vue', 'react', 'javascript', 'livewire', 'inertia', 'alpine'],
            'deployment' => ['deploy', 'server', 'docker', 'forge', 'vapor', 'nginx'],
            'packages' => ['package', 'composer', 'packagist', 'library', 'vendor'],
            'architecture' => ['architecture', 'pattern', 'solid', 'ddd', 'repository', 'service'],
        ];

        $topics = [];
        $lowerText = strtolower($text);

        foreach ($keywords as $topic => $words) {
            foreach ($words as $word) {
                if (str_contains($lowerText, $word)) {
                    $topics[] = $topic;
                    break; // Move to next topic once match found
                }
            }
        }

        return array_unique($topics);
    }

    /**
     * Assess content difficulty level
     */
    private function assessDifficulty(string $content): string
    {
        $advancedKeywords = [
            'advanced', 'complex', 'architecture', 'patterns', 'optimization',
            'performance tuning', 'scalability', 'enterprise'
        ];
        
        $beginnerKeywords = [
            'beginner', 'introduction', 'getting started', 'basics', 'tutorial',
            'learn', 'first', 'simple', 'easy'
        ];
        
        $lowerContent = strtolower($content);
        
        // Check for advanced indicators
        foreach ($advancedKeywords as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                return 'advanced';
            }
        }
        
        // Check for beginner indicators
        foreach ($beginnerKeywords as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                return 'beginner';
            }
        }
        
        return 'intermediate'; // Default fallback
    }

    /**
     * Estimate reading time based on word count
     */
    private function estimateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, ceil($wordCount / 250)); // 250 words per minute average
    }
}
```

## Algorithm Examples

### Example 1: New User with Testing Interest

```php
// Initial setup
$user = User::create(['name' => 'Alice Dev', 'email' => 'alice@example.com']);

// User selects interests during onboarding
UserInterest::create(['user_id' => $user->id, 'topic' => 'testing', 'weight' => 8]);
UserInterest::create(['user_id' => $user->id, 'topic' => 'apis', 'weight' => 6]);
UserInterest::create(['user_id' => $user->id, 'topic' => 'performance', 'weight' => 4]);

// Sample articles in system
$articles = [
    [
        'title' => 'Testing File Uploads with Pest',
        'source' => 'freek.dev',
        'topics' => ['testing', 'pest'],
        'created_at' => now()->subHours(2),
        'likes_count' => 45,
        'reading_time' => 6
    ],
    [
        'title' => 'Building Fast APIs with Laravel',
        'source' => 'Laravel News', 
        'topics' => ['apis', 'performance'],
        'created_at' => now()->subDays(1),
        'likes_count' => 78,
        'reading_time' => 10
    ],
    [
        'title' => 'Vue.js Components Tutorial',
        'source' => 'Laravel Daily',
        'topics' => ['frontend', 'vue'],
        'created_at' => now()->subDays(2),
        'likes_count' => 23,
        'reading_time' => 8
    ]
];

// Algorithm scoring results:
/*
1. "Testing File Uploads with Pest" = 7.8/10
   - Interest Score: 0.8 (strong testing match) × 0.4 = 0.32
   - Quality Score: 0.85 (trusted source + good length) × 0.25 = 0.21  
   - Recency Score: 1.0 (very recent) × 0.2 = 0.20
   - Engagement Score: 0.45 (decent engagement) × 0.1 = 0.045
   - Diversity Score: 1.0 (new source) × 0.05 = 0.05
   - Total: 0.32 + 0.21 + 0.20 + 0.045 + 0.05 = 0.825

2. "Building Fast APIs with Laravel" = 7.2/10
   - Interest Score: 0.7 (apis + performance match) × 0.4 = 0.28
   - Quality Score: 0.9 (Laravel News + good engagement) × 0.25 = 0.225
   - Recency Score: 0.8 (1 day old) × 0.2 = 0.16
   - Engagement Score: 0.78 (high engagement) × 0.1 = 0.078
   - Diversity Score: 1.0 × 0.05 = 0.05
   - Total: 0.28 + 0.225 + 0.16 + 0.078 + 0.05 = 0.793

3. "Vue.js Components Tutorial" = 3.1/10
   - Interest Score: 0.0 (no topic match) × 0.4 = 0.0
   - Quality Score: 0.7 × 0.25 = 0.175
   - Recency Score: 0.6 × 0.2 = 0.12
   - Engagement Score: 0.23 × 0.1 = 0.023
   - Diversity Score: 1.0 × 0.05 = 0.05
   - Total: 0.0 + 0.175 + 0.12 + 0.023 + 0.05 = 0.368
*/
```

### Example 2: Learning and Adaptation

```php
// After Alice reads and bookmarks the testing article
$article = Article::where('title', 'Testing File Uploads with Pest')->first();

// Track reading interaction
UserArticleInteraction::create([
    'user_id' => $user->id,
    'article_id' => $article->id,
    'read_at' => now(),
    'reading_time' => 8, // minutes spent reading
    'bookmarked' => true,
]);

// Update user interest
$testingInterest = UserInterest::where('user_id', $user->id)
    ->where('topic', 'testing')->first();

$testingInterest->increaseEngagement();
// weight: 8.0 → 8.6 (8.0 + 0.1 reading + 0.5 bookmark boost)
// engagement_count: 0 → 1
// last_engaged_at: updated to now()

// Next recommendation request - testing articles now score even higher!
```

### Example 3: Diversity Scoring

```php
// User has read 3 articles from Laravel News this week
$user->articleInteractions()
    ->whereHas('article', fn($q) => $q->where('source', 'Laravel News'))
    ->where('read_at', '>=', now()->subDays(7))
    ->count(); // Returns 3

// New Laravel News article gets diversity penalty
$diversityScore = 0.2; // Heavy penalty for overrepresented source

// While article from new source gets full diversity score
$diversityScore = 1.0; // No penalty
```

## Performance Optimization

### Caching Strategy

```php
// app/Services/ContentRecommendationService.php

public function getRecommendationsForUser(User $user, int $limit = 10): Collection
{
    $cacheKey = "recommendations:user:{$user->id}:limit:{$limit}";
    
    return Cache::remember($cacheKey, now()->addHours(2), function () use ($user, $limit) {
        // ... existing recommendation logic
    });
}

// Invalidate cache when user interacts with content
public function invalidateUserCache(User $user): void
{
    Cache::tags(["user:{$user->id}"])->flush();
}
```

### Database Indexing

```sql
-- Optimize frequent queries
CREATE INDEX idx_articles_topics ON articles USING GIN (topics);
CREATE INDEX idx_articles_created_source ON articles (created_at, source);
CREATE INDEX idx_user_interests_user_weight ON user_interests (user_id, weight);
CREATE INDEX idx_interactions_user_read ON user_article_interactions (user_id, read_at);
```

### Background Processing

```php
// app/Jobs/UpdateRecommendationsJob.php
class UpdateRecommendationsJob implements ShouldQueue
{
    public function handle(ContentRecommendationService $service)
    {
        User::chunk(100, function ($users) use ($service) {
            foreach ($users as $user) {
                $recommendations = $service->getRecommendationsForUser($user, 20);
                Cache::put("recommendations:user:{$user->id}", $recommendations, now()->addHours(6));
            }
        });
    }
}

// Schedule in app/Console/Kernel.php
$schedule->job(new UpdateRecommendationsJob)->hourly();
```

## Algorithm Benefits

### 1. **Personalization**
- Learns from individual user behavior
- Adapts to changing interests over time
- Balances exploration vs exploitation

### 2. **Content Quality**
- Prioritizes trusted Laravel sources
- Considers content depth and engagement
- Filters out low-quality content

### 3. **Freshness**
- Promotes recent content while preserving evergreen value
- Adapts to Laravel ecosystem changes
- Highlights trending topics

### 4. **Diversity**
- Prevents echo chambers
- Encourages exploration of new sources
- Maintains topic variety

### 5. **Scalability**
- Efficient database queries with proper indexing
- Cacheable recommendations
- Background processing for heavy computations

## Future Enhancements

### Phase 1: Advanced ML
```php
// Collaborative filtering
"Users who liked this also liked..."

// Content similarity using NLP
$similarity = $this->calculateSemanticSimilarity($article1, $article2);

// Neural networks for complex pattern recognition
$predictions = $this->neuralNetwork->predict($userFeatures, $contentFeatures);
```

### Phase 2: Real-time Features
```php
// Live trending topics
$trending = $this->detectTrendingTopics(timeWindow: '24h');

// A/B testing framework
$variant = $this->getRecommendationVariant($user);

// Real-time feedback loop
$this->adjustScoring($user, $feedback);
```

### Phase 3: Advanced Analytics
```php
// Recommendation effectiveness metrics
$ctr = $this->calculateClickThroughRate($user, $period);
$engagement = $this->measureEngagementQuality($user, $period);
$retention = $this->calculateUserRetention($cohort, $period);
```

## Monitoring and Metrics

### Key Performance Indicators

```php
// app/Services/RecommendationAnalyticsService.php
class RecommendationAnalyticsService 
{
    public function getMetrics(User $user, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'click_through_rate' => $this->calculateCTR($user, $startDate, $endDate),
            'reading_completion_rate' => $this->calculateCompletionRate($user, $startDate, $endDate),
            'bookmark_rate' => $this->calculateBookmarkRate($user, $startDate, $endDate),
            'topic_coverage' => $this->calculateTopicCoverage($user, $startDate, $endDate),
            'source_diversity' => $this->calculateSourceDiversity($user, $startDate, $endDate),
        ];
    }
}
```

### Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Click-through Rate | >15% | Clicks / Impressions |
| Reading Completion | >60% | Articles read fully / Articles opened |
| Bookmark Rate | >8% | Bookmarks / Articles shown |
| Return Visits | >40% | Users returning within 7 days |
| Topic Diversity | >5 topics | Unique topics in recommendations |

## Automatic Seen Post Detection

### Overview

One of the most critical aspects of a good recommendation system is preventing users from seeing the same content repeatedly. Our algorithm implements automatic "seen post" detection using multiple tracking mechanisms.

### Detection Methods

#### 1. Viewport Intersection Tracking

```javascript
// Automatically detect when articles come into view
setupIntersectionObserver() {
    this.intersectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const articleId = entry.target.dataset.articleId;
                if (!this.seenArticles.has(articleId)) {
                    this.seenArticles.add(articleId);
                    this.trackSeen(articleId);
                }
            }
        });
    }, {
        threshold: 0.5, // Article must be 50% visible
        rootMargin: '0px'
    });

    // Observe all article elements
    document.querySelectorAll('[data-article-id]').forEach(article => {
        this.intersectionObserver.observe(article);
    });
}
```

#### 2. Click and Engagement Tracking

```php
// Laravel Routes for tracking
Route::post('/api/articles/{article}/track-click', [FeedController::class, 'trackClick']);
Route::post('/api/articles/{article}/track-reading', [FeedController::class, 'trackReading']);
Route::post('/api/articles/track-impressions', [FeedController::class, 'trackImpressions']);
```

#### 3. Scroll-based Engagement

```javascript
// Track how much of each article user has seen
updateScrollPercentage() {
    const articles = document.querySelectorAll('[data-article-id]');
    articles.forEach(article => {
        const rect = article.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            const articleId = article.dataset.articleId;
            const scrollPercentage = this.calculateScrollPercentage(rect);
            
            if (scrollPercentage > 0.3) { // 30% threshold
                this.markAsEngaged(articleId);
            }
        }
    });
}
```

### Database Implementation

```sql
-- Enhanced interaction tracking
CREATE TABLE user_article_interactions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    article_id BIGINT,
    
    -- Automatic tracking fields
    seen_at TIMESTAMP, -- When article appeared in feed
    clicked_at TIMESTAMP, -- When user clicked on article
    scroll_percentage FLOAT DEFAULT 0, -- How much user scrolled
    
    -- Manual interaction fields  
    liked BOOLEAN DEFAULT FALSE,
    bookmarked BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP, -- When user actually read content
    reading_time INT, -- Time spent reading
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_user_seen (user_id, seen_at),
    INDEX idx_user_article (user_id, article_id),
    INDEX idx_engagement (user_id, scroll_percentage, seen_at)
);
```

### Smart Exclusion Logic

```php
// app/Services/ContentRecommendationService.php

private function getExcludedArticleIds(User $user): array
{
    return $user->articleInteractions()
        ->where(function ($query) {
            $query->whereNotNull('seen_at') // Already seen
                  ->orWhereNotNull('read_at') // Already read
                  ->orWhereNotNull('clicked_at') // Already clicked
                  ->orWhere('scroll_percentage', '>=', 0.5) // Significantly engaged
                  ->orWhere('liked', true) // User liked it
                  ->orWhere('bookmarked', true); // User bookmarked it
        })
        ->pluck('article_id')
        ->toArray();
}

// Advanced exclusion with time-based re-showing
private function getAdvancedExcludedArticleIds(User $user): array
{
    $excludedIds = [];
    
    // Permanently exclude highly engaged content
    $permanentlyExcluded = $user->articleInteractions()
        ->where(function ($query) {
            $query->whereNotNull('read_at')
                  ->orWhere('liked', true)
                  ->orWhere('bookmarked', true)
                  ->orWhere('scroll_percentage', '>=', 0.7);
        })
        ->pluck('article_id')
        ->toArray();
    
    // Temporarily exclude recently seen content (24 hours)
    $temporarilyExcluded = $user->articleInteractions()
        ->whereNotNull('seen_at')
        ->where('seen_at', '>=', now()->subHours(24))
        ->whereNull('read_at') // But allow re-showing if not read
        ->where('scroll_percentage', '<', 0.3) // And low engagement
        ->pluck('article_id')
        ->toArray();
    
    return array_merge($permanentlyExcluded, $temporarilyExcluded);
}
```

### Frontend Implementation

```javascript
class ArticleTracker {
    constructor() {
        this.seenArticles = new Set();
        this.engagementData = new Map();
        this.init();
    }

    init() {
        this.setupIntersectionObserver();
        this.setupClickTracking();
        this.setupScrollTracking();
        this.trackInitiallyVisible();
    }

    // Track articles visible when page loads
    trackInitiallyVisible() {
        const visibleArticleIds = [];
        document.querySelectorAll('[data-article-id]').forEach(article => {
            if (this.isElementVisible(article)) {
                const articleId = article.dataset.articleId;
                visibleArticleIds.push(articleId);
                this.seenArticles.add(articleId);
            }
        });

        if (visibleArticleIds.length > 0) {
            this.sendBatchImpression(visibleArticleIds);
        }
    }

    // Batch impressions for better performance
    sendBatchImpression(articleIds) {
        fetch('/api/articles/track-impressions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken()
            },
            body: JSON.stringify({ article_ids: articleIds })
        }).catch(console.error);
    }

    // Smart engagement detection
    detectEngagement(articleId, element) {
        const engagement = {
            viewTime: this.calculateViewTime(articleId),
            scrollPercentage: this.calculateScrollPercentage(element),
            clicks: this.getClickCount(articleId),
            timeOnPage: Date.now() - this.startTime
        };

        // Determine if user is genuinely engaged
        if (engagement.scrollPercentage > 0.5 || 
            engagement.viewTime > 30 || 
            engagement.clicks > 0) {
            this.markAsEngaged(articleId, engagement);
        }
    }
}

// Initialize tracking
document.addEventListener('DOMContentLoaded', () => {
    window.articleTracker = new ArticleTracker();
});
```

### Laravel Backend Integration

```php
// app/Http/Controllers/FeedController.php

public function trackImpressions(Request $request)
{
    if (!auth()->check()) {
        return response()->json(['status' => 'guest']);
    }

    $user = auth()->user();
    $articleIds = $request->input('article_ids', []);

    // Batch process impressions for performance
    $interactions = [];
    foreach ($articleIds as $articleId) {
        $interactions[] = [
            'user_id' => $user->id,
            'article_id' => $articleId,
            'seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Use upsert for better performance
    UserArticleInteraction::upsert(
        $interactions,
        ['user_id', 'article_id'], // Unique keys
        ['seen_at', 'updated_at'] // Update these fields
    );

    return response()->json(['status' => 'success', 'tracked' => count($articleIds)]);
}

// Background job for processing engagement data
class ProcessEngagementDataJob implements ShouldQueue
{
    public function handle()
    {
        // Update user interests based on engagement patterns
        UserArticleInteraction::with(['user', 'article'])
            ->where('created_at', '>=', now()->subHour())
            ->whereNotNull('seen_at')
            ->chunk(100, function ($interactions) {
                foreach ($interactions as $interaction) {
                    $this->updateUserInterests($interaction);
                }
            });
    }

    private function updateUserInterests(UserArticleInteraction $interaction)
    {
        $engagementScore = $interaction->getEngagementScore();
        
        if ($engagementScore > 0.3) { // Threshold for meaningful engagement
            foreach ($interaction->article->topics as $topic) {
                $interest = UserInterest::firstOrCreate(
                    ['user_id' => $interaction->user_id, 'topic' => $topic],
                    ['weight' => 1]
                );
                
                // Adjust interest based on engagement level
                $boost = $engagementScore * 0.2;
                $interest->weight = min(10, $interest->weight + $boost);
                $interest->save();
            }
        }
    }
}
```

### Performance Optimizations

#### 1. Efficient Database Queries

```php
// app/Services/ContentRecommendationService.php

public function getRecommendationsForUser(User $user, int $limit = 10): Collection
{
    // Use Redis for caching excluded articles
    $cacheKey = "excluded_articles:user:{$user->id}";
    
    $excludedIds = Cache::remember($cacheKey, 3600, function () use ($user) {
        return $this->getExcludedArticleIds($user);
    });

    // Efficient query with proper indexing
    $articles = Article::whereNotIn('id', $excludedIds)
        ->where('created_at', '>=', now()->subDays(30))
        ->with(['interactions' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->get();

    // Rest of recommendation logic...
}

// Invalidate cache when user interacts
public function invalidateUserExclusionCache(User $user): void
{
    Cache::forget("excluded_articles:user:{$user->id}");
}
```

#### 2. JavaScript Performance

```javascript
// Debounced tracking to prevent API spam
class DebouncedTracker {
    constructor() {
        this.pendingImpressions = new Set();
        this.debounceTimeout = null;
    }

    trackImpression(articleId) {
        this.pendingImpressions.add(articleId);
        
        // Debounce API calls
        clearTimeout(this.debounceTimeout);
        this.debounceTimeout = setTimeout(() => {
            this.flushImpressions();
        }, 500);
    }

    flushImpressions() {
        if (this.pendingImpressions.size > 0) {
            const articleIds = Array.from(this.pendingImpressions);
            this.sendBatchImpression(articleIds);
            this.pendingImpressions.clear();
        }
    }
}
```

### Advanced Features

#### 1. Re-surfacing Algorithm

```php
// app/Services/ContentResurfacingService.php

class ContentResurfacingService
{
    /**
     * Re-surface high-quality content user might have missed
     */
    public function getResurfacedContent(User $user, int $limit = 3): Collection
    {
        // Find highly-rated content user saw but didn't engage with
        $candidateIds = $user->articleInteractions()
            ->whereNotNull('seen_at')
            ->whereNull('clicked_at')
            ->where('scroll_percentage', '<', 0.2)
            ->where('seen_at', '<=', now()->subDays(7)) // Wait a week
            ->pluck('article_id');

        return Article::whereIn('id', $candidateIds)
            ->where('likes_count', '>=', 100) // High engagement threshold
            ->orderByDesc('likes_count')
            ->limit($limit)
            ->get();
    }
}
```

#### 2. A/B Testing for Exclusion Logic

```php
// app/Services/ABTestingService.php

class ABTestingService
{
    public function getExclusionStrategy(User $user): string
    {
        // Simple A/B test based on user ID
        $variants = ['strict', 'moderate', 'relaxed'];
        return $variants[$user->id % 3];
    }
}

// In ContentRecommendationService
private function getExcludedArticleIds(User $user): array
{
    $strategy = app(ABTestingService::class)->getExclusionStrategy($user);
    
    switch ($strategy) {
        case 'strict':
            return $this->getStrictExcludedIds($user);
        case 'moderate':
            return $this->getModerateExcludedIds($user);
        case 'relaxed':
            return $this->getRelaxedExcludedIds($user);
    }
}
```

### Monitoring and Analytics

```php
// app/Services/RecommendationAnalyticsService.php

public function getSeenPostMetrics(User $user, Carbon $startDate, Carbon $endDate): array
{
    $interactions = $user->articleInteractions()
        ->whereBetween('seen_at', [$startDate, $endDate])
        ->get();

    return [
        'total_seen' => $interactions->count(),
        'clicked_rate' => $interactions->whereNotNull('clicked_at')->count() / $interactions->count(),
        'engagement_rate' => $interactions->where('scroll_percentage', '>', 0.3)->count() / $interactions->count(),
        'avg_scroll_percentage' => $interactions->avg('scroll_percentage'),
        'read_rate' => $interactions->whereNotNull('read_at')->count() / $interactions->count(),
    ];
}
```

### Benefits of Automatic Seen Post Detection

1. **Improved User Experience**: No repetitive content
2. **Better Engagement**: More diverse, fresh recommendations
3. **Accurate Analytics**: Understand true user preferences
4. **Efficient Resource Usage**: Don't waste API calls on seen content
5. **Adaptive Learning**: System learns from viewing patterns

### Implementation Checklist

- [ ] Add seen_at, clicked_at, scroll_percentage to interactions table
- [ ] Implement JavaScript intersection observer for viewport tracking
- [ ] Create batch impression tracking endpoint
- [ ] Update recommendation service to exclude seen content
- [ ] Add background job for processing engagement data
- [ ] Implement caching for excluded article IDs
- [ ] Add A/B testing for different exclusion strategies
- [ ] Set up monitoring for seen post metrics
- [ ] Test performance with large datasets
- [ ] Document API endpoints for frontend team

## Conclusion

This enhanced content recommendation algorithm with automatic seen post detection provides LaravelSense with:

1. **Intelligent Content Filtering**: Automatically prevents showing repeated content
2. **Behavioral Learning**: Adapts based on viewing patterns and engagement
3. **Performance Optimization**: Efficient tracking with minimal overhead
4. **User Experience**: Fresh, diverse content in every session
5. **Analytics Insights**: Deep understanding of user behavior

The system combines multiple detection methods (viewport intersection, click tracking, scroll behavior) to create a comprehensive understanding of user interaction with content, ensuring recommendations remain fresh and engaging while learning from user preferences.