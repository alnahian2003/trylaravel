<?php

namespace App\Console\Commands;

use App\Services\Scraping\Scrapers\Sites\CodecourseScraper;
use App\Services\Scraping\Scrapers\Sites\SpatieScraper;
use App\Services\Scraping\Scrapers\Sites\StitcherScraper;
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
            $this->info('HTML length: '.strlen($html));

            // Test Browsershot
            $this->info('Testing Browsershot...');
            $scraper = new CodecourseScraper;
            $this->info('Scraper created successfully');

            $config = $scraper->getConfiguration();
            $this->info('Configuration loaded: '.$config['name']);

            // Test a single article
            $testUrl = 'https://codecourse.com/articles/form-submit-confirmation-with-alpinejs';
            $this->info("Testing single article: {$testUrl}");

            $articleData = $scraper->scrapeArticle($testUrl);
            $this->info('Article scraped successfully!');
            $this->info('Title: '.$articleData->title);
            $this->info('Author: '.$articleData->author);
            $this->info('Content length: '.strlen($articleData->content));

            // Test Spatie scraper
            $this->info('');
            $this->info('Testing Spatie scraper...');
            $spatieScraper = new SpatieScraper;
            $this->info('Spatie scraper created successfully');

            $spatieConfig = $spatieScraper->getConfiguration();
            $this->info('Spatie configuration loaded: '.$spatieConfig['name']);

            // Test a single Spatie article
            $spatieTestUrl = 'https://spatie.be/blog/why-we-use-react';
            $this->info("Testing Spatie article: {$spatieTestUrl}");

            $spatieArticleData = $spatieScraper->scrapeArticle($spatieTestUrl);
            $this->info('Spatie article scraped successfully!');
            $this->info('Title: '.$spatieArticleData->title);
            $this->info('Author: '.$spatieArticleData->author);
            $this->info('Tags: '.implode(', ', $spatieArticleData->tags));
            $this->info('Content length: '.strlen($spatieArticleData->content));

            // Test Stitcher scraper
            $this->info('');
            $this->info('Testing Stitcher scraper...');
            $stitcherScraper = new StitcherScraper;
            $this->info('Stitcher scraper created successfully');

            $stitcherConfig = $stitcherScraper->getConfiguration();
            $this->info('Stitcher configuration loaded: '.$stitcherConfig['name']);

            // Test a single Stitcher article
            $stitcherTestUrl = 'https://stitcher.io/blog/vendor-locked';
            $this->info("Testing Stitcher article: {$stitcherTestUrl}");

            $stitcherArticleData = $stitcherScraper->scrapeArticle($stitcherTestUrl);
            $this->info('Stitcher article scraped successfully!');
            $this->info('Title: '.$stitcherArticleData->title);
            $this->info('Author: '.$stitcherArticleData->author);
            $this->info('Tags: '.implode(', ', $stitcherArticleData->tags));
            $this->info('Content length: '.strlen($stitcherArticleData->content));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Test failed: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
