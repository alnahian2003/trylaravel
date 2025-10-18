<?php

namespace App\Services\Scraping\Scrapers\Sites;

use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class LaravelNewsScraper extends AbstractScraper
{
    public function getSiteKey(): string
    {
        return 'laravel-news';
    }

    public function getBaseUrl(): string
    {
        return 'https://laravel-news.com';
    }

    protected function getArticlesListUrl(): string
    {
        return 'https://laravel-news.com';
    }

    protected function extractTitle(Crawler $crawler): string
    {
        $titleSelectors = [
            'h1.entry-title',
            'h1.post-title',
            'h1',
            'meta[property="og:title"]',
            'meta[name="twitter:title"]',
            'title',
        ];

        foreach ($titleSelectors as $selector) {
            try {
                $title = $crawler->filter($selector)->first();
                if ($title->count() > 0) {
                    $text = str_contains($selector, 'meta')
                        ? $title->attr('content')
                        : $title->text();

                    if (! empty($text)) {
                        // Clean up title by removing site name
                        $text = str_replace(' - Laravel News', '', $text);
                        $text = str_replace('Laravel News - ', '', $text);
                        $text = preg_replace('/\s*\|\s*Laravel News\s*$/', '', $text);
                        return $this->cleanText($text);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \RuntimeException('Could not extract title from page');
    }

    protected function extractContent(Crawler $crawler): string
    {
        // Check if we're blocked by Cloudflare
        if (str_contains($crawler->html(), 'Just a moment...') || 
            str_contains($crawler->html(), 'cloudflare') ||
            str_contains($crawler->html(), 'cf-browser-verification')) {
            throw new \RuntimeException('Laravel News is protected by Cloudflare bot protection. Scraping may require different approach or API access.');
        }

        $contentSelectors = [
            '.entry-content',
            '.post-content',
            '.article-content',
            'article .content',
            '.post-body',
            'article',
            '.single-post-content',
            '[role="main"] article',
            '.content',
            '.post',
            '.entry',
            'main',
        ];

        foreach ($contentSelectors as $selector) {
            try {
                $content = $crawler->filter($selector)->first();
                if ($content->count() > 0) {
                    $htmlContent = $content->html();
                    if (strlen(strip_tags($htmlContent)) > 100) { // Ensure we have meaningful content
                        return $this->cleanContent($htmlContent);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \RuntimeException('Could not extract content from page. Site may be protected or have different structure.');
    }

    protected function extractExcerpt(Crawler $crawler): string
    {
        $excerptSelectors = [
            'meta[name="description"]',
            'meta[property="og:description"]',
            'meta[name="twitter:description"]',
            '.excerpt',
            '.post-excerpt',
            '.entry-summary',
            '.post-summary',
            'article p:first-of-type',
        ];

        foreach ($excerptSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $text = str_contains($selector, 'meta')
                        ? $element->attr('content')
                        : $element->text();

                    if (! empty($text)) {
                        return $this->cleanText($text);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return '';
    }

    protected function extractAuthor(Crawler $crawler): string
    {
        $authorSelectors = [
            '.author-name',
            '.post-author',
            '.entry-author',
            '.author .name',
            '.byline .author',
            'meta[name="author"]',
            '[rel="author"]',
            '.post-meta .author',
            '.article-meta .author',
        ];

        foreach ($authorSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $text = str_contains($selector, 'meta')
                        ? $element->attr('content')
                        : $element->text();

                    if (! empty($text)) {
                        // Clean author name
                        $text = str_replace('By ', '', $text);
                        $text = str_replace('by ', '', $text);
                        $text = trim($text, ' •-|');
                        return $this->cleanText($text);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Default author for Laravel News
        return 'Laravel News Team';
    }

    protected function extractAuthorAvatar(Crawler $crawler): ?string
    {
        try {
            $avatarSelectors = [
                '.author-avatar img',
                '.author img',
                '.post-author img',
                '.author-image img',
                'img[alt*="author"]',
                'img[src*="avatar"]',
                'img[src*="gravatar"]',
            ];

            foreach ($avatarSelectors as $selector) {
                $avatar = $crawler->filter($selector)->first();
                if ($avatar->count() > 0) {
                    $src = $avatar->attr('src');
                    if ($src) {
                        return $this->normalizeUrl($src);
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    protected function extractPublishedAt(Crawler $crawler): ?string
    {
        $dateSelectors = [
            'time[datetime]',
            'meta[property="article:published_time"]',
            '.post-date time',
            '.entry-date time',
            '.post-date',
            '.entry-date',
            '.date',
            '.published',
            '.post-meta .date',
        ];

        foreach ($dateSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $dateText = str_contains($selector, 'meta')
                        ? $element->attr('content')
                        : ($element->attr('datetime') ?? $element->text());

                    if (! empty($dateText)) {
                        return $this->parseDateFromText($dateText);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    protected function extractTags(Crawler $crawler): array
    {
        $tags = [];

        try {
            $tagSelectors = [
                '.tags a',
                '.post-tags a',
                '.entry-tags a',
                '.tag-list a',
                '.tag a',
                '.categories a',
                '[class*="tag"] a',
                '.post-meta .tags a',
            ];

            foreach ($tagSelectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$tags) {
                    $text = $this->cleanText($node->text());
                    $text = ltrim($text, '#');
                    if (! empty($text) && ! in_array($text, ['Blog', 'Post', 'Article', 'News'])) {
                        $tags[] = $text;
                    }
                });
            }

            // Check meta keywords
            try {
                $metaKeywords = $crawler->filter('meta[name="keywords"]')->first();
                if ($metaKeywords->count() > 0) {
                    $keywords = $metaKeywords->attr('content');
                    if ($keywords) {
                        $keywordArray = array_map('trim', explode(',', $keywords));
                        foreach ($keywordArray as $keyword) {
                            if (! empty($keyword) && ! in_array($keyword, $tags)) {
                                $tags[] = $keyword;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }

            // Default tags for Laravel News content
            if (empty($tags)) {
                $tags[] = 'Laravel';
                $tags[] = 'PHP';
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return array_unique(array_filter($tags));
    }

    protected function extractCategories(Crawler $crawler): array
    {
        $categories = [];

        try {
            $categorySelectors = [
                '.category a',
                '.categories a',
                '.post-category a',
                '.entry-category a',
                '.breadcrumb a',
                '.post-meta .category a',
            ];

            foreach ($categorySelectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$categories) {
                    $text = $this->cleanText($node->text());
                    if (! empty($text) && ! in_array($text, ['Home', 'Blog', 'News'])) {
                        $categories[] = $text;
                    }
                });
            }

            // Default category for Laravel News
            if (empty($categories)) {
                $categories[] = 'Laravel News';
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return array_unique(array_filter($categories));
    }

    protected function extractFeaturedImage(Crawler $crawler): ?string
    {
        $imageSelectors = [
            'meta[property="og:image"]',
            'meta[name="twitter:image"]',
            'meta[name="twitter:image:src"]',
            '.featured-image img',
            '.post-image img',
            '.entry-image img',
            'article img:first-of-type',
            '.post-thumbnail img',
        ];

        foreach ($imageSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $src = str_contains($selector, 'meta')
                        ? $element->attr('content')
                        : $element->attr('src');

                    if (! empty($src)) {
                        return $this->normalizeUrl($src);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    protected function extractReadingTime(Crawler $crawler): int
    {
        try {
            // Try to find reading time indicators
            $readingTimeSelectors = [
                '.reading-time',
                '.read-time',
                '.estimated-reading-time',
                '.post-meta .time',
                '[class*="reading-time"]',
                '[class*="read-time"]',
            ];

            foreach ($readingTimeSelectors as $selector) {
                $elements = $crawler->filter($selector);
                foreach ($elements as $element) {
                    $text = $element->textContent;
                    if (preg_match('/(\d+)\s*min/i', $text, $matches)) {
                        return (int) $matches[1];
                    }
                }
            }

            // Estimate reading time based on content length
            $content = $this->extractContent($crawler);
            $wordCount = str_word_count(strip_tags($content));

            return max(1, round($wordCount / 200)); // 200 words per minute average
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getPageUrl(string $baseUrl, int $page): string
    {
        // Laravel News pagination
        if ($page === 1) {
            return $baseUrl;
        }
        
        return $baseUrl . '/page/' . $page;
    }

    private function parseDateFromText(string $dateText): ?string
    {
        // Handle various date formats
        if (preg_match('/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/', $dateText, $matches)) {
            try {
                return \Carbon\Carbon::parse($dateText)->toISOString();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $dateText, $matches)) {
            try {
                return \Carbon\Carbon::parse($matches[1])->toISOString();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (preg_match('/(\w+\s+\d{1,2},?\s+\d{4})/', $dateText, $matches)) {
            try {
                return \Carbon\Carbon::parse($matches[1])->toISOString();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        try {
            return \Carbon\Carbon::parse($dateText)->toISOString();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanContent(string $content): string
    {
        // Remove script tags
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
        
        // Remove style tags
        $content = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $content);

        // Remove navigation elements
        $content = preg_replace('/<nav\b[^>]*>.*?<\/nav>/mi', '', $content);

        // Remove footer elements
        $content = preg_replace('/<footer\b[^>]*>.*?<\/footer>/mi', '', $content);

        // Remove aside elements (sidebars)
        $content = preg_replace('/<aside\b[^>]*>.*?<\/aside>/mi', '', $content);

        // Remove comment forms and sections
        $content = preg_replace('/<div[^>]*class="[^"]*comment[^"]*"[^>]*>.*?<\/div>/msi', '', $content);
        $content = preg_replace('/<section[^>]*class="[^"]*comment[^"]*"[^>]*>.*?<\/section>/msi', '', $content);

        // Remove social sharing buttons
        $content = preg_replace('/<div[^>]*class="[^"]*share[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove ads and sponsored content
        $content = preg_replace('/<div[^>]*class="[^"]*ad[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}