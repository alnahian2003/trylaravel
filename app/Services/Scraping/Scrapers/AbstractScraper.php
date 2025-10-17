<?php

namespace App\Services\Scraping\Scrapers;

use App\Services\Scraping\Configuration\ScrapingConfig;
use App\Services\Scraping\Contracts\ScrapableInterface;
use App\Services\Scraping\Core\BrowserFactory;
use App\Services\Scraping\Core\ScrapedData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractScraper implements ScrapableInterface
{
    protected array $config;
    protected ScrapingConfig $configManager;

    public function __construct()
    {
        $this->configManager = new ScrapingConfig();
        $this->config = $this->configManager->loadConfiguration($this->getSiteKey());
    }

    public function scrapeArticles(int $maxPages = 1): Collection
    {
        $articles = collect();
        $currentPage = 1;
        $articlesUrl = $this->getArticlesListUrl();

        while ($currentPage <= $maxPages) {
            try {
                $this->respectRateLimit();
                
                Log::info("Scraping articles page {$currentPage} for {$this->getSiteKey()}");
                
                $pageUrl = $currentPage === 1 ? $articlesUrl : $this->getPageUrl($articlesUrl, $currentPage);
                $html = BrowserFactory::scrapeUrl($pageUrl, $this->config['browser']);
                $crawler = new Crawler($html);

                $articleLinks = $this->extractArticleLinks($crawler);
                
                if ($articleLinks->isEmpty()) {
                    Log::info("No more articles found on page {$currentPage} for {$this->getSiteKey()}");
                    break;
                }

                foreach ($articleLinks as $link) {
                    try {
                        $this->respectRateLimit();
                        $articleData = $this->scrapeArticle($link);
                        $articles->push($articleData);
                    } catch (\Exception $e) {
                        Log::error("Failed to scrape article {$link}: " . $e->getMessage());
                    }
                }

                $currentPage++;

            } catch (\Exception $e) {
                Log::error("Failed to scrape page {$currentPage} for {$this->getSiteKey()}: " . $e->getMessage());
                break;
            }
        }

        return $articles;
    }

    public function scrapeArticle(string $url): ScrapedData
    {
        try {
            Log::info("Scraping article: {$url}");
            
            $html = BrowserFactory::scrapeUrl($url, $this->config['browser']);
            $crawler = new Crawler($html);

            return new ScrapedData(
                title: $this->extractTitle($crawler),
                content: $this->extractContent($crawler),
                url: $url,
                excerpt: $this->extractExcerpt($crawler),
                author: $this->extractAuthor($crawler),
                authorAvatar: $this->extractAuthorAvatar($crawler),
                authorEmail: $this->extractAuthorEmail($crawler),
                publishedAt: $this->extractPublishedAt($crawler),
                tags: $this->extractTags($crawler),
                categories: $this->extractCategories($crawler),
                featuredImage: $this->extractFeaturedImage($crawler),
                meta: $this->extractMeta($crawler),
                sourceUrl: $url,
                readingTime: $this->extractReadingTime($crawler),
            );

        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to scrape article {$url}: " . $e->getMessage(), 0, $e);
        }
    }

    public function getConfiguration(): array
    {
        return $this->config;
    }

    protected function respectRateLimit(): void
    {
        if (isset($this->config['rate_limit'])) {
            usleep($this->config['rate_limit'] * 1000);
        }
    }

    protected function retryOnFailure(callable $operation, int $maxAttempts = null): mixed
    {
        $maxAttempts = $maxAttempts ?? $this->config['retry']['max_attempts'];
        $delay = $this->config['retry']['delay_ms'];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $operation();
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                
                Log::warning("Operation failed (attempt {$attempt}/{$maxAttempts}): " . $e->getMessage());
                usleep($delay * 1000 * $attempt);
            }
        }
    }

    protected function extractArticleLinks(Crawler $crawler): Collection
    {
        $links = collect();
        $selector = $this->config['selectors']['article_links'];

        $crawler->filter($selector)->each(function (Crawler $node) use ($links) {
            $href = $node->attr('href');
            if ($href) {
                $links->push($this->normalizeUrl($href));
            }
        });

        return $links->unique();
    }

    protected function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return rtrim($this->getBaseUrl(), '/') . $url;
        }

        return rtrim($this->getBaseUrl(), '/') . '/' . $url;
    }

    protected function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    protected function getPageUrl(string $baseUrl, int $page): string
    {
        return $baseUrl . '?page=' . $page;
    }

    abstract protected function getArticlesListUrl(): string;

    abstract protected function extractTitle(Crawler $crawler): string;
    
    abstract protected function extractContent(Crawler $crawler): string;
    
    abstract protected function extractExcerpt(Crawler $crawler): string;

    protected function extractAuthor(Crawler $crawler): string
    {
        return $this->config['selectors']['author'] ?? '' 
            ? $this->cleanText($crawler->filter($this->config['selectors']['author'])->text(''))
            : '';
    }

    protected function extractAuthorAvatar(Crawler $crawler): ?string
    {
        return $this->config['selectors']['author_avatar'] ?? '' 
            ? $crawler->filter($this->config['selectors']['author_avatar'])->attr('src')
            : null;
    }

    protected function extractAuthorEmail(Crawler $crawler): ?string
    {
        return null;
    }

    protected function extractPublishedAt(Crawler $crawler): ?string
    {
        return $this->config['selectors']['published_at'] ?? '' 
            ? $crawler->filter($this->config['selectors']['published_at'])->attr('datetime') 
              ?? $crawler->filter($this->config['selectors']['published_at'])->text('')
            : null;
    }

    protected function extractTags(Crawler $crawler): array
    {
        $tags = [];
        $selector = $this->config['selectors']['tags'] ?? '';
        
        if ($selector) {
            $crawler->filter($selector)->each(function (Crawler $node) use (&$tags) {
                $tags[] = $this->cleanText($node->text());
            });
        }

        return array_filter($tags);
    }

    protected function extractCategories(Crawler $crawler): array
    {
        $categories = [];
        $selector = $this->config['selectors']['categories'] ?? '';
        
        if ($selector) {
            $crawler->filter($selector)->each(function (Crawler $node) use (&$categories) {
                $categories[] = $this->cleanText($node->text());
            });
        }

        return array_filter($categories);
    }

    protected function extractFeaturedImage(Crawler $crawler): ?string
    {
        return $this->config['selectors']['featured_image'] ?? '' 
            ? $crawler->filter($this->config['selectors']['featured_image'])->attr('src')
            : null;
    }

    protected function extractMeta(Crawler $crawler): array
    {
        return [
            'scraped_at' => now()->toISOString(),
            'scraper_version' => '1.0.0',
            'site_key' => $this->getSiteKey(),
        ];
    }

    protected function extractReadingTime(Crawler $crawler): int
    {
        $selector = $this->config['selectors']['reading_time'] ?? '';
        
        if ($selector) {
            $text = $crawler->filter($selector)->text('');
            if (preg_match('/(\d+)/', $text, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}