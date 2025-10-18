<?php

namespace App\Services\Scraping\Core;

use App\Models\Post;
use App\Models\Source;
use App\Services\Scraping\Contracts\ScrapableInterface;
use App\Services\Scraping\Scrapers\Sites\CodecourseScraper;
use App\Services\Scraping\Scrapers\Sites\LaravelNewsScraper;
use App\Services\Scraping\Scrapers\Sites\MattStaufferScraper;
use App\Services\Scraping\Scrapers\Sites\SpatieScraper;
use App\Services\Scraping\Scrapers\Sites\StitcherScraper;
use App\Services\Scraping\Scrapers\Sites\TightenScraper;
use App\Services\Scraping\Transformers\PostTransformer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScraperManager
{
    protected array $scrapers = [
        'codecourse' => CodecourseScraper::class,
        'laravel-news' => LaravelNewsScraper::class,
        'matt-stauffer' => MattStaufferScraper::class,
        'spatie' => SpatieScraper::class,
        'stitcher' => StitcherScraper::class,
        'tighten' => TightenScraper::class,
    ];

    protected PostTransformer $transformer;

    public function __construct()
    {
        $this->transformer = new PostTransformer;
    }

    public function getScraper(string $siteKey): ScrapableInterface
    {
        if (! isset($this->scrapers[$siteKey])) {
            throw new \InvalidArgumentException("Scraper not found for site: {$siteKey}");
        }

        $scraperClass = $this->scrapers[$siteKey];

        return new $scraperClass;
    }

    public function registerScraper(string $siteKey, string $scraperClass): void
    {
        if (! is_subclass_of($scraperClass, ScrapableInterface::class)) {
            throw new \InvalidArgumentException('Scraper class must implement ScrapableInterface');
        }

        $this->scrapers[$siteKey] = $scraperClass;
    }

    public function getAvailableScrapers(): array
    {
        return array_keys($this->scrapers);
    }

    public function scrapeAndStore(string $siteKey, int $maxPages = 1, bool $updateExisting = false): array
    {
        $scraper = $this->getScraper($siteKey);
        $source = $this->getOrCreateSource($siteKey, $scraper);

        Log::info("Starting scrape for {$siteKey}, max pages: {$maxPages}");

        $results = [
            'site_key' => $siteKey,
            'pages_scraped' => 0,
            'articles_found' => 0,
            'articles_created' => 0,
            'articles_updated' => 0,
            'articles_skipped' => 0,
            'errors' => [],
        ];

        try {
            $articles = $scraper->scrapeArticles($maxPages);
            $results['articles_found'] = $articles->count();

            foreach ($articles as $articleData) {
                try {
                    $result = $this->processArticle($articleData, $source, $updateExisting);
                    $results[$result]++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Failed to process article {$articleData->url}: ".$e->getMessage();
                    Log::error("Failed to process article {$articleData->url}: ".$e->getMessage());
                }
            }

            $source->update([
                'last_fetched_at' => now(),
                'posts_count' => Post::where('source_url', 'LIKE', "%{$scraper->getBaseUrl()}%")->count(),
            ]);

            Log::info("Completed scrape for {$siteKey}", $results);

        } catch (\Exception $e) {
            $results['errors'][] = 'Scraper failed: '.$e->getMessage();
            Log::error("Scraper failed for {$siteKey}: ".$e->getMessage());
        }

        return $results;
    }

    public function scrapeArticle(string $siteKey, string $url, bool $updateExisting = false): array
    {
        $scraper = $this->getScraper($siteKey);
        $source = $this->getOrCreateSource($siteKey, $scraper);

        Log::info("Scraping single article from {$siteKey}: {$url}");

        try {
            $articleData = $scraper->scrapeArticle($url);
            $result = $this->processArticle($articleData, $source, $updateExisting);

            return [
                'success' => true,
                'action' => $result,
                'url' => $url,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to scrape article {$url}: ".$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ];
        }
    }

    protected function processArticle(ScrapedData $articleData, Source $source, bool $updateExisting): string
    {
        return DB::transaction(function () use ($articleData, $updateExisting) {
            $existingPost = Post::where('source_url', $articleData->url)->first();

            if ($existingPost) {
                if ($updateExisting) {
                    $this->transformer->updatePost($existingPost, $articleData);

                    return 'articles_updated';
                } else {
                    return 'articles_skipped';
                }
            }

            $this->transformer->createPost($articleData);

            return 'articles_created';
        });
    }

    protected function getOrCreateSource(string $siteKey, ScrapableInterface $scraper): Source
    {
        $config = $scraper->getConfiguration();
        $siteName = $config['name'] ?? ucfirst($siteKey);

        return Source::firstOrCreate(
            ['url' => $scraper->getBaseUrl()],
            [
                'name' => $siteName,
                'feed_url' => $scraper->getBaseUrl().'/articles',
                'description' => "Articles from {$siteName}",
                'is_active' => true,
                'metadata' => [
                    'site_key' => $siteKey,
                    'scraper_config' => $config,
                ],
            ]
        );
    }

    public function getSiteHealth(string $siteKey): array
    {
        try {
            $scraper = $this->getScraper($siteKey);
            $testUrl = $scraper->getBaseUrl();

            $start = microtime(true);
            BrowserFactory::scrapeUrl($testUrl, ['timeout' => 10000]);
            $responseTime = round((microtime(true) - $start) * 1000);

            return [
                'site_key' => $siteKey,
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'last_checked' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return [
                'site_key' => $siteKey,
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_checked' => now()->toISOString(),
            ];
        }
    }

    public function getAllSitesHealth(): Collection
    {
        return collect($this->getAvailableScrapers())
            ->map(fn ($siteKey) => $this->getSiteHealth($siteKey));
    }
}
