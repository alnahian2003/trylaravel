<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ContentRankingService;
use Illuminate\Console\Command;

class CalculateRankingScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:calculate {--force : Force recalculation of all scores}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and cache ranking scores for posts';

    public function __construct(
        private ContentRankingService $rankingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Calculating ranking scores...');

        $query = Post::published();

        // Only calculate for posts that need updating unless forced
        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('ranking_calculated_at')
                    ->orWhere('ranking_calculated_at', '<', now()->subHours(6));
            });
        }

        $posts = $query->get();
        $progressBar = $this->output->createProgressBar($posts->count());

        foreach ($posts as $post) {
            $score = $this->rankingService->calculateContentScore($post);

            $post->update([
                'ranking_score' => $score,
                'ranking_calculated_at' => now(),
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Updated ranking scores for {$posts->count()} posts.");

        return Command::SUCCESS;
    }
}
