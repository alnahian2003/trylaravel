# 🚀 Scraper Quick Reference

## Commands

```bash
# Health check
php artisan scrape:articles codecourse --health

# Scrape 1 page
php artisan scrape:articles codecourse --pages=1

# Scrape and update existing
php artisan scrape:articles codecourse --pages=2 --update

# Test scraper
php artisan test:scraper
```

## Adding a New Site (5 Steps)

### 1. Create Config File
`app/Services/Scraping/Configuration/configs/newsite.yaml`

```yaml
site_key: "newsite"
name: "New Site"
base_url: "https://newsite.com"
rate_limit: 2000

selectors:
  article_links: ".article-link"
  title: "h1"
  content: ".content"
  author: ".author"
```

### 2. Create Scraper Class
`app/Services/Scraping/Scrapers/Sites/NewsiteScraper.php`

```php
<?php
namespace App\Services\Scraping\Scrapers\Sites;
use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class NewsiteScraper extends AbstractScraper
{
    public function getSiteKey(): string { return 'newsite'; }
    public function getBaseUrl(): string { return 'https://newsite.com'; }
    protected function getArticlesListUrl(): string { return 'https://newsite.com/articles'; }
    
    protected function extractTitle(Crawler $crawler): string {
        return $this->cleanText($crawler->filter('h1')->text());
    }
    
    protected function extractContent(Crawler $crawler): string {
        return $crawler->filter('.content')->html();
    }
    
    protected function extractExcerpt(Crawler $crawler): string {
        return $this->cleanText($crawler->filter('.excerpt')->text());
    }
}
```

### 3. Register in ScraperManager
Add to `app/Services/Scraping/Core/ScraperManager.php`:

```php
protected array $scrapers = [
    'codecourse' => CodecourseScraper::class,
    'newsite' => NewsiteScraper::class, // Add this
];
```

### 4. Test
```bash
php artisan scrape:articles newsite --health
php artisan scrape:articles newsite --pages=1
```

### 5. Verify Database
```bash
php artisan tinker --execute="echo Post::where('source_url', 'LIKE', '%newsite%')->count() . ' articles scraped';"
```

## Common CSS Selectors

```yaml
selectors:
  # Articles list page
  article_links: "a[href*='/articles/']"          # Links containing '/articles/'
  article_links: ".post-title a"                  # Links in post titles
  article_links: "h2 a, h3 a"                     # Links in headings
  
  # Article page
  title: "h1"                                     # Main heading
  title: ".post-title, .article-title"           # Class-based selectors
  
  content: ".content, .post-content, article"    # Main content area
  content: ".prose"                               # Tailwind prose class
  
  excerpt: "meta[name='description']"             # Meta description
  excerpt: ".excerpt, .summary"                  # Excerpt sections
  
  author: ".author, .byline"                      # Author sections
  author: "meta[name='author']"                   # Meta author
  
  published_at: "time[datetime]"                  # Time elements
  published_at: ".date, .published"              # Date sections
  
  tags: ".tag, .category"                         # Tag elements
  tags: ".tags a"                                 # Links in tags section
  
  featured_image: "meta[property='og:image']"     # Open Graph image
  featured_image: ".featured-image img"          # Featured image
```

## Troubleshooting Checklist

```bash
# 1. Check site accessibility
curl -I https://targetsite.com

# 2. Test health
php artisan scrape:articles sitename --health

# 3. Verify config file exists
ls app/Services/Scraping/Configuration/configs/sitename.yaml

# 4. Check Chrome/Puppeteer
npm list puppeteer

# 5. Test single article
php artisan test:scraper

# 6. Check database
php artisan tinker --execute="echo Post::count() . ' total posts';"
```

## Config Template

```yaml
site_key: "SITENAME"
name: "Site Display Name"
base_url: "https://example.com"
articles_url: "https://example.com/articles"
rate_limit: 2000  # 2 seconds between requests

selectors:
  article_links: "REQUIRED - CSS selector for article links"
  title: "REQUIRED - CSS selector for article title"
  content: "REQUIRED - CSS selector for article content"
  excerpt: "Optional - excerpt/description"
  author: "Optional - author name"
  author_avatar: "Optional - author image"
  published_at: "Optional - publish date"
  reading_time: "Optional - reading time"
  tags: "Optional - article tags"
  categories: "Optional - article categories"
  featured_image: "Optional - main image"

browser:
  timeout: 30000
  user_agent: "Mozilla/5.0 (compatible; YourBot/1.0)"
  wait_until_network_idle: true

retry:
  max_attempts: 3
  delay_ms: 1000
```

## Database Schema

Scraped articles are stored as Post models with:

```php
// Required fields (auto-filled)
'title'        // Article title
'slug'         // URL-friendly slug  
'content'      // Full article content
'excerpt'      // Article summary
'type'         // PostType::POST
'status'       // PostStatus::PUBLISHED
'published_at' // Publication date

// Optional fields
'author_name'    // Author name
'author_avatar'  // Author image URL
'source_url'     // Original article URL
'featured_image' // Main image URL
'tags'          // Array of tags
'categories'    // Array of categories
'meta'          // Additional metadata
```

## Programmatic Usage

```php
use App\Services\Scraping\Core\ScraperManager;

$manager = new ScraperManager();

// Health check
$health = $manager->getSiteHealth('codecourse');

// Scrape articles
$results = $manager->scrapeAndStore('codecourse', $pages = 1, $update = false);

// Scrape single article
$result = $manager->scrapeArticle('codecourse', 'https://codecourse.com/articles/some-post');

// Available scrapers
$scrapers = $manager->getAvailableScrapers();
```

## Error Messages & Solutions

| Error | Solution |
|-------|----------|
| `Configuration file not found` | Create YAML config file |
| `Scraper not found for site` | Register scraper in ScraperManager |
| `Could not extract title` | Update title selector in config |
| `Command timed out` | Reduce pages count or increase rate_limit |
| `Chrome launch failed` | Install Puppeteer: `npm install puppeteer` |
| `No articles found` | Update article_links selector |

## Performance Tips

- Start with `--pages=1` for testing
- Use `rate_limit: 3000` for slower sites
- Test selectors in browser DevTools first  
- Monitor site changes regularly
- Use `--update` sparingly to avoid duplicate content
- Schedule scraping during off-peak hours

---

**Happy Scraping! 🕸️**