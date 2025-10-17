<?php

namespace App\Console\Commands;

use App\Services\Scraping\Core\BrowserFactory;
use App\Services\Scraping\Scrapers\Sites\CodecourseScraper;
use Illuminate\Console\Command;

class TestScraper extends Command
{
    protected $signature = 'test:scraper';
    protected $description = 'Test scraper functionality';

    public function handle(): int
    {
        $this->info('Testing basic HTML scraping...');
        
        try {
            // Test basic HTTP request first
            $this->info('Testing basic HTTP request...');
            $html = file_get_contents('https://codecourse.com/articles');
            $this->info('HTML length: ' . strlen($html));
            
            // Test Browsershot
            $this->info('Testing Browsershot...');
            $scraper = new CodecourseScraper();
            $this->info('Scraper created successfully');
            
            $config = $scraper->getConfiguration();
            $this->info('Configuration loaded: ' . $config['name']);
            
            // Test a single article
            $testUrl = 'https://codecourse.com/articles/form-submit-confirmation-with-alpinejs';
            $this->info("Testing single article: {$testUrl}");
            
            $articleData = $scraper->scrapeArticle($testUrl);
            $this->info('Article scraped successfully!');
            $this->info('Title: ' . $articleData->title);
            $this->info('Author: ' . $articleData->author);
            $this->info('Content length: ' . strlen($articleData->content));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Test failed: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
