<?php

namespace App\Console\Commands;

use App\Services\Scraping\Core\ScraperManager;
use Illuminate\Console\Command;

class ScrapeArticles extends Command
{
    protected $signature = 'scrape:articles 
                           {site : The site key to scrape (e.g., codecourse)}
                           {--pages=1 : Number of pages to scrape}
                           {--update : Update existing articles}
                           {--health : Check site health only}';

    protected $description = 'Scrape articles from configured websites';

    public function handle(ScraperManager $scraperManager): int
    {
        $siteKey = $this->argument('site');
        $pages = (int) $this->option('pages');
        $updateExisting = $this->option('update');
        $healthCheck = $this->option('health');

        if (!in_array($siteKey, $scraperManager->getAvailableScrapers())) {
            $this->error("Unknown site key: {$siteKey}");
            $this->info("Available scrapers: " . implode(', ', $scraperManager->getAvailableScrapers()));
            return Command::FAILURE;
        }

        if ($healthCheck) {
            return $this->checkHealth($scraperManager, $siteKey);
        }

        $this->info("Starting scrape for {$siteKey}...");
        $this->info("Pages to scrape: {$pages}");
        $this->info("Update existing: " . ($updateExisting ? 'Yes' : 'No'));

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        try {
            $results = $scraperManager->scrapeAndStore($siteKey, $pages, $updateExisting);
            
            $progressBar->finish();
            $this->newLine(2);

            $this->displayResults($results);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine(2);
            $this->error("Scraping failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function checkHealth(ScraperManager $scraperManager, string $siteKey): int
    {
        $this->info("Checking health for {$siteKey}...");
        
        $health = $scraperManager->getSiteHealth($siteKey);
        
        if ($health['status'] === 'healthy') {
            $this->info("✅ {$siteKey} is healthy");
            $this->info("Response time: {$health['response_time_ms']}ms");
        } else {
            $this->error("❌ {$siteKey} is unhealthy");
            $this->error("Error: {$health['error']}");
        }

        return $health['status'] === 'healthy' ? Command::SUCCESS : Command::FAILURE;
    }

    protected function displayResults(array $results): void
    {
        $this->info("📊 Scraping Results for {$results['site_key']}:");
        $this->newLine();

        $this->table([
            'Metric', 'Count'
        ], [
            ['Articles Found', $results['articles_found']],
            ['Articles Created', $results['articles_created']],
            ['Articles Updated', $results['articles_updated']],
            ['Articles Skipped', $results['articles_skipped']],
            ['Errors', count($results['errors'])],
        ]);

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error("❌ Errors encountered:");
            foreach ($results['errors'] as $error) {
                $this->line("   • {$error}");
            }
        }

        if ($results['articles_created'] > 0 || $results['articles_updated'] > 0) {
            $this->newLine();
            $this->info("✅ Scraping completed successfully!");
        }
    }
}
