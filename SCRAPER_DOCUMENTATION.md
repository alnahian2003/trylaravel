# 🚀 Reusable Web Scraper System

A powerful, scalable web scraping system built for Laravel using Spatie Browsershot and Symfony DomCrawler. Designed to handle 40-50 websites with ease.

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Adding New Sites](#adding-new-sites)
- [Commands](#commands)
- [Troubleshooting](#troubleshooting)
- [Advanced Features](#advanced-features)

## 🎯 Overview

This scraper system provides:

- **Reusable Architecture** - Easily add new websites
- **Rate Limiting** - Respectful scraping with configurable delays
- **Error Handling** - Retry mechanisms and failure recovery
- **Data Transformation** - Clean data that maps to your Post model
- **Health Monitoring** - Check site availability
- **Queue Support** - Background processing (extensible)

## 🏗️ Architecture

```
app/Services/Scraping/
├── Contracts/
│   ├── ScrapableInterface.php       # Main scraper contract
│   ├── ConfigurableInterface.php    # Configuration management
│   └── TransformableInterface.php   # Data transformation
├── Core/
│   ├── ScraperManager.php          # Orchestrates scraping operations
│   ├── BrowserFactory.php          # Browsershot wrapper
│   └── ScrapedData.php             # Data transfer object
├── Scrapers/
│   ├── AbstractScraper.php         # Base scraper functionality
│   └── Sites/
│       └── CodecourseScraper.php   # Site-specific implementation
├── Transformers/
│   └── PostTransformer.php         # Transforms scraped data to Post model
└── Configuration/
    ├── ScrapingConfig.php          # Configuration loader
    └── configs/
        └── codecourse.yaml         # Site-specific config
```

### Key Components

1. **ScrapableInterface** - Contract all scrapers must implement
2. **AbstractScraper** - Base class with common functionality
3. **ScraperManager** - Central orchestrator for all scraping operations
4. **PostTransformer** - Converts scraped data to Laravel Post models
5. **YAML Configs** - Site-specific configuration files

## 🔧 Installation

### Dependencies

The scraper system uses these packages:

```bash
composer require spatie/browsershot symfony/dom-crawler symfony/yaml
```

### System Requirements

- **Node.js & npm** - For Puppeteer (Browsershot dependency)
- **Chrome/Chromium** - Headless browser engine
- **PHP 8.2+** - For modern PHP features

### Setup

1. **Install Puppeteer** (if not already installed):
```bash
npm install puppeteer
```

2. **Run Migrations** (user_id made nullable for scraped sources):
```bash
php artisan migrate
```

3. **Verify Installation**:
```bash
php artisan scrape:articles codecourse --health
```

## ⚙️ Configuration

### Site Configuration (YAML)

Each website needs a YAML configuration file in `app/Services/Scraping/Configuration/configs/`:

```yaml
# codecourse.yaml
site_key: "codecourse"
name: "Codecourse"
base_url: "https://codecourse.com"
articles_url: "https://codecourse.com/articles"
rate_limit: 2000  # milliseconds between requests

selectors:
  article_links: "h2 a[href*='/articles/']"
  title: "h1"
  content: ".prose"
  excerpt: "meta[name='description']"
  author: ".border-t .font-semibold.bg-gradient-to-r"
  author_avatar: "img[src*='codecourse-avatars']"
  published_at: ".text-xs.uppercase.font-medium"
  reading_time: ".text-xs.uppercase.font-medium"
  tags: ".rounded-full"
  featured_image: "meta[property='og:image']"

browser:
  timeout: 30000
  user_agent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
  wait_until_network_idle: true
  headers:
    Accept: "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
    Accept-Language: "en-US,en;q=0.5"

retry:
  max_attempts: 3
  delay_ms: 1000
```

### Required Fields

- `site_key` - Unique identifier
- `name` - Human-readable site name
- `base_url` - Website base URL
- `selectors.title` - CSS selector for article title
- `selectors.content` - CSS selector for article content

### Optional Fields

- `rate_limit` - Delay between requests (default: 2000ms)
- `browser.*` - Browsershot configuration
- `retry.*` - Retry behavior
- All other selectors for enhanced data extraction

## 🚀 Usage

### Basic Commands

```bash
# Health check - verify site is accessible
php artisan scrape:articles codecourse --health

# Scrape 1 page (typically 10 articles)
php artisan scrape:articles codecourse --pages=1

# Scrape multiple pages
php artisan scrape:articles codecourse --pages=3

# Update existing articles
php artisan scrape:articles codecourse --pages=1 --update
```

### Programmatic Usage

```php
use App\Services\Scraping\Core\ScraperManager;

$scraperManager = new ScraperManager();

// Health check
$health = $scraperManager->getSiteHealth('codecourse');
if ($health['status'] === 'healthy') {
    echo "Site is accessible!";
}

// Scrape and store articles
$results = $scraperManager->scrapeAndStore('codecourse', $pages = 2, $updateExisting = false);

echo "Created: {$results['articles_created']} articles";
echo "Updated: {$results['articles_updated']} articles";
echo "Errors: " . count($results['errors']);

// Scrape single article
$result = $scraperManager->scrapeArticle('codecourse', 'https://codecourse.com/articles/some-article');
```

### Data Flow

1. **ScraperManager** loads site configuration
2. **Site-specific scraper** extracts HTML using Browsershot
3. **DomCrawler** parses HTML and extracts data
4. **PostTransformer** converts to Post model format
5. **Database** stores as published Post records

## 🆕 Adding New Sites

### Step 1: Create Configuration

Create `app/Services/Scraping/Configuration/configs/yoursite.yaml`:

```yaml
site_key: "techcrunch"
name: "TechCrunch"
base_url: "https://techcrunch.com"
articles_url: "https://techcrunch.com/category/startups/"
rate_limit: 3000

selectors:
  article_links: ".post-block__title__link"
  title: ".article__title"
  content: ".article-content"
  excerpt: ".article__excerpt"
  author: ".article__byline a"
  published_at: ".article__date"
  tags: ".tags a"
```

### Step 2: Create Scraper Class

Create `app/Services/Scraping/Scrapers/Sites/TechcrunchScraper.php`:

```php
<?php

namespace App\Services\Scraping\Scrapers\Sites;

use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class TechcrunchScraper extends AbstractScraper
{
    public function getSiteKey(): string
    {
        return 'techcrunch';
    }

    public function getBaseUrl(): string
    {
        return 'https://techcrunch.com';
    }

    protected function getArticlesListUrl(): string
    {
        return 'https://techcrunch.com/category/startups/';
    }

    protected function extractTitle(Crawler $crawler): string
    {
        $title = $crawler->filter('.article__title')->first();
        if ($title->count() > 0) {
            return $this->cleanText($title->text());
        }
        throw new \RuntimeException('Could not extract title');
    }

    protected function extractContent(Crawler $crawler): string
    {
        $content = $crawler->filter('.article-content')->first();
        if ($content->count() > 0) {
            return $content->html();
        }
        throw new \RuntimeException('Could not extract content');
    }

    protected function extractExcerpt(Crawler $crawler): string
    {
        $excerpt = $crawler->filter('.article__excerpt')->first();
        return $excerpt->count() > 0 ? $this->cleanText($excerpt->text()) : '';
    }

    // Override other methods as needed for site-specific logic
}
```

### Step 3: Register Scraper

Add to `ScraperManager::$scrapers` array:

```php
protected array $scrapers = [
    'codecourse' => CodecourseScraper::class,
    'techcrunch' => TechcrunchScraper::class, // Add this line
];
```

### Step 4: Test

```bash
# Test health
php artisan scrape:articles techcrunch --health

# Test scraping
php artisan scrape:articles techcrunch --pages=1
```

## 📝 Commands

### Scrape Articles

```bash
php artisan scrape:articles {site} {--pages=1} {--update} {--health}
```

**Arguments:**
- `site` - Site key (e.g., codecourse, techcrunch)

**Options:**
- `--pages=N` - Number of pages to scrape (default: 1)
- `--update` - Update existing articles instead of skipping
- `--health` - Check site health only

**Examples:**
```bash
# Health check
php artisan scrape:articles codecourse --health

# Scrape 2 pages
php artisan scrape:articles codecourse --pages=2

# Update existing articles
php artisan scrape:articles codecourse --pages=1 --update
```

### Test Scraper

```bash
php artisan test:scraper
```

Tests basic scraper functionality with codecourse.com.

## 🛠️ Troubleshooting

### Common Issues

#### 1. Timeout Errors
```
(Command timed out after 60000 ms)
```

**Solutions:**
- Reduce `--pages` count
- Increase `rate_limit` in config
- Check site accessibility
- Verify Chrome/Puppeteer installation

#### 2. No Articles Found
```
No more articles found on page 1
```

**Solutions:**
- Verify `selectors.article_links` in config
- Check if site structure changed
- Test health check first

#### 3. Content Extraction Errors
```
Could not extract title from page
```

**Solutions:**
- Inspect site HTML structure
- Update CSS selectors in config
- Check for JavaScript-rendered content

#### 4. Chrome/Puppeteer Issues
```
Failed to launch chrome
```

**Solutions:**
```bash
# Install/reinstall Puppeteer
npm install puppeteer

# For Ubuntu/Debian systems
sudo apt-get install chromium-browser

# For macOS
brew install chromium
```

### Debug Mode

Enable debug logging by adding to your scraper:

```php
protected function extractTitle(Crawler $crawler): string
{
    \Log::info('HTML content:', ['html' => $crawler->html()]);
    
    // Your extraction logic...
}
```

### Testing Individual Components

```php
// Test configuration loading
$config = new ScrapingConfig();
$siteConfig = $config->loadConfiguration('codecourse');

// Test browser factory
$html = BrowserFactory::scrapeUrl('https://codecourse.com/articles');

// Test data transformation
$scrapedData = new ScrapedData(/* ... */);
$transformer = new PostTransformer();
$postData = $transformer->transform($scrapedData);
```

## 🔥 Advanced Features

### Custom Data Transformers

Create site-specific transformers:

```php
namespace App\Services\Scraping\Transformers;

class TechcrunchTransformer extends PostTransformer
{
    public function transform(ScrapedData $data): array
    {
        $postData = parent::transform($data);
        
        // Custom logic for TechCrunch
        $postData['categories'] = ['Technology', 'Startups'];
        $postData['meta']['source_type'] = 'tech_news';
        
        return $postData;
    }
}
```

### Background Processing

Add to your queue system:

```php
// Create a job
php artisan make:job ProcessScraping

// In ProcessScraping.php
public function handle(ScraperManager $scraperManager): void
{
    $scraperManager->scrapeAndStore($this->siteKey, $this->pages);
}

// Dispatch job
ProcessScraping::dispatch('codecourse', 2);
```

### Scheduled Scraping

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('scrape:articles codecourse --pages=1')
             ->hourly()
             ->withoutOverlapping();
             
    $schedule->command('scrape:articles techcrunch --pages=1')
             ->dailyAt('08:00')
             ->withoutOverlapping();
}
```

### Custom Selectors Per Article Type

```yaml
selectors:
  # Different selectors for different content types
  article_links: 
    posts: ".post-link"
    videos: ".video-link"
    tutorials: ".tutorial-link"
  
  title:
    posts: "h1.post-title"
    videos: "h1.video-title"
```

### Rate Limiting Strategies

```yaml
rate_limit: 2000  # Base delay

# Progressive delays
rate_limit_strategy: "progressive"  # linear, exponential, fixed
rate_limit_multiplier: 1.5

# Respect robots.txt
respect_robots_txt: true

# Custom user agents per site
browser:
  user_agent: "YourBot/1.0 (+https://yoursite.com/bot)"
```

### Error Handling & Monitoring

```php
// Custom error handlers
class ScrapingErrorHandler
{
    public function handleScrapingError(\Exception $e, string $siteKey): void
    {
        \Log::error("Scraping failed for {$siteKey}", [
            'error' => $e->getMessage(),
            'site' => $siteKey,
            'timestamp' => now(),
        ]);
        
        // Send notifications
        // Disable problematic sites temporarily
        // Adjust rate limits automatically
    }
}
```

### Health Monitoring Dashboard

Create health check endpoints:

```php
// routes/web.php
Route::get('/admin/scraper/health', function (ScraperManager $manager) {
    $health = $manager->getAllSitesHealth();
    return view('admin.scraper-health', compact('health'));
});
```

## 📊 Metrics & Monitoring

### Available Metrics

- Articles scraped per site
- Success/failure rates
- Response times
- Error types and frequencies
- Queue processing times

### Example Monitoring

```php
// Get scraping statistics
$stats = [
    'total_articles' => Post::where('source_url', 'LIKE', '%codecourse%')->count(),
    'last_scraped' => Source::where('url', 'https://codecourse.com')->value('last_fetched_at'),
    'success_rate' => '95%', // Calculate from logs
];
```

## 🎯 Best Practices

### Configuration
- Use descriptive selector names
- Test selectors in browser dev tools
- Keep rate limits respectful (2-5 seconds)
- Always include User-Agent headers

### Error Handling
- Implement graceful fallbacks
- Log detailed error information
- Use exponential backoff for retries
- Monitor site changes proactively

### Performance
- Scrape during off-peak hours
- Use queue jobs for large operations
- Cache configuration files
- Implement circuit breakers for failing sites

### Maintenance
- Regular health checks
- Update selectors when sites change
- Monitor robots.txt changes
- Keep dependencies updated

## 📚 Resources

- [Spatie Browsershot Documentation](https://github.com/spatie/browsershot)
- [Symfony DomCrawler Documentation](https://symfony.com/doc/current/components/dom_crawler.html)
- [CSS Selectors Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors)
- [Web Scraping Ethics](https://blog.scrapehero.com/web-scraping-ethics/)

## 🤝 Contributing

When adding new scrapers:

1. Follow the existing architecture patterns
2. Include comprehensive error handling
3. Test thoroughly with different page structures
4. Document any site-specific quirks
5. Respect the site's robots.txt and terms of service

---

**Built with ❤️ for scalable web scraping**