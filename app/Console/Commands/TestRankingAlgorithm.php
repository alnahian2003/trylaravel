<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ContentRankingService;
use Illuminate\Console\Command;

class TestRankingAlgorithm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:test {--limit=10 : Number of posts to rank}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the content ranking algorithm with existing posts';

    /**
     * Execute the console command.
     */
    public function handle(ContentRankingService $rankingService): int
    {
        $limit = $this->option('limit');
        
        $this->info("Testing Content Ranking Algorithm");
        $this->line("=====================================");

        // Get published posts
        $posts = Post::published()->limit(50)->get();
        
        if ($posts->isEmpty()) {
            $this->warn('No published posts found. Please add some posts first.');
            return 1;
        }

        $this->info("Found {$posts->count()} published posts. Ranking top {$limit}...");
        $this->newLine();

        // Rank posts
        $rankedPosts = $rankingService->rankForAnonymousUser($posts)->take($limit);

        // Display results
        $this->table(
            ['Rank', 'Title', 'Source', 'Score', 'Views', 'Likes', 'Published'],
            $rankedPosts->map(function (Post $post, $index) use ($rankingService) {
                $score = $rankingService->calculateContentScore($post);
                $domain = $this->extractDomain($post->source_url);
                
                return [
                    $index + 1,
                    $this->truncate($post->title, 40),
                    $domain ?: 'Unknown',
                    round($score, 2),
                    $post->views_count,
                    $post->likes_count,
                    $post->published_at?->diffForHumans(),
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info("Top ranking factors:");
        
        // Show breakdown for top post
        if ($rankedPosts->isNotEmpty()) {
            $topPost = $rankedPosts->first();
            $breakdown = $rankingService->getScoreBreakdown($topPost);
            
            $this->line("• Source Authority: {$breakdown['source_authority']['score']} (weight: {$breakdown['source_authority']['weight']})");
            $this->line("• Recency: " . round($breakdown['recency']['score'], 2) . " (weight: {$breakdown['recency']['weight']})");
            $this->line("• Engagement: " . round($breakdown['engagement']['score'], 2) . " (weight: {$breakdown['engagement']['weight']})");
            $this->line("• Total Score: " . round($breakdown['total_score'], 2));
        }

        $this->newLine();
        
        // Show hero content
        $heroContent = $rankingService->getHeroContent(3);
        if ($heroContent->isNotEmpty()) {
            $this->info("Hero Content (High Quality Posts):");
            $heroContent->each(function (Post $post, $index) use ($rankingService) {
                $score = $rankingService->calculateContentScore($post);
                $this->line(($index + 1) . ". {$post->title} (Score: " . round($score, 2) . ")");
            });
        }

        $this->newLine();
        
        // Show trending content  
        $trendingContent = $rankingService->getTrendingPosts(3);
        if ($trendingContent->isNotEmpty()) {
            $this->info("Trending Posts (Recent High Engagement):");
            $trendingContent->each(function (Post $post, $index) use ($rankingService) {
                $score = $rankingService->calculateContentScore($post);
                $this->line(($index + 1) . ". {$post->title} (Score: " . round($score, 2) . ")");
            });
        }

        return 0;
    }

    private function extractDomain(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        
        return preg_replace('/^www\./', '', strtolower($host));
    }

    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - 3) . '...';
    }
}
