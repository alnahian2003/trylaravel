<?php

namespace App\Services\Scraping\Scrapers\Sites;

use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class MattStaufferScraper extends AbstractScraper
{
    public function getSiteKey(): string
    {
        return 'matt-stauffer';
    }

    public function getBaseUrl(): string
    {
        return 'https://mattstauffer.com';
    }

    protected function getArticlesListUrl(): string
    {
        return 'https://mattstauffer.com/blog';
    }

    protected function extractTitle(Crawler $crawler): string
    {
        $titleSelectors = [
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
                        $text = str_replace(' | MattStauffer.com', '', $text);
                        $text = str_replace('MattStauffer.com - ', '', $text);
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
        $contentSelectors = [
            'article',
            '.post-content',
            '.entry-content',
            '.article-content',
            'main article',
            '[role="main"] article',
            '.content',
        ];

        foreach ($contentSelectors as $selector) {
            try {
                $content = $crawler->filter($selector)->first();
                if ($content->count() > 0) {
                    return $this->cleanContent($content->html());
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \RuntimeException('Could not extract content from page');
    }

    protected function extractExcerpt(Crawler $crawler): string
    {
        $excerptSelectors = [
            'meta[name="description"]',
            'meta[property="og:description"]',
            'meta[name="twitter:description"]',
            '.excerpt',
            '.post-excerpt',
            '.summary',
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
            '.byline .author',
            'meta[name="author"]',
            '[rel="author"]',
            '.post-meta .author',
        ];

        foreach ($authorSelectors as $selector) {
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

        // Default author for Matt Stauffer's blog
        return 'Matt Stauffer';
    }

    protected function extractAuthorAvatar(Crawler $crawler): ?string
    {
        try {
            $avatarSelectors = [
                '.author-avatar img',
                '.author img',
                'img[alt*="Matt"]',
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
            '.post-date',
            '.date',
            '.published',
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
                '.tag-list a',
                '.categories a',
                '[class*="tag"] a',
                '.post-meta .tags a',
            ];

            foreach ($tagSelectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$tags) {
                    $text = $this->cleanText($node->text());
                    $text = ltrim($text, '#');
                    if (! empty($text) && ! in_array($text, ['Blog', 'Post', 'Article'])) {
                        $tags[] = $text;
                    }
                });
            }

            // Extract tags from content or URL patterns
            $url = $crawler->getUri();
            if ($url && preg_match('/\/blog\/([^\/]+)/', $url, $matches)) {
                $urlSlug = $matches[1];
                // Common Laravel/PHP topics from Matt's blog
                $phpTopics = ['laravel', 'php', 'vue', 'javascript', 'css', 'nginx', 'mysql', 'artisan', 'forge', 'homestead'];
                foreach ($phpTopics as $topic) {
                    if (str_contains($urlSlug, $topic)) {
                        $tags[] = ucfirst($topic);
                    }
                }
            }

            // Default tags for Matt Stauffer content
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
                '.breadcrumb a',
                '.post-category',
                '.post-meta .category a',
            ];

            foreach ($categorySelectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$categories) {
                    $text = $this->cleanText($node->text());
                    if (! empty($text) && ! in_array($text, ['Home', 'Blog'])) {
                        $categories[] = $text;
                    }
                });
            }

            // Default category for Matt Stauffer's blog
            if (empty($categories)) {
                $categories[] = 'Programming';
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
            'article img:first-of-type',
            '.hero-image img',
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
                '.post-meta .time',
                '[class*="reading-time"]',
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
        return $baseUrl . '/blog';
    }

    protected function extractArticleLinks(Crawler $crawler): \Illuminate\Support\Collection
    {
        $links = collect();

        try {
            // Matt Stauffer's blog has article links in the format: a[href*="/blog/"]
            $selector = 'a[href*="/blog/"]:not([href="/blog"]):not([href="/blog/"])';

            $crawler->filter($selector)->each(function (Crawler $node) use ($links) {
                $href = $node->attr('href');
                if ($href && ! str_ends_with($href, '/blog') && ! str_ends_with($href, '/blog/')) {
                    $links->push($this->normalizeUrl($href));
                }
            });

            // Also check for links in the article list structure
            $crawler->filter('ul li a[href*="/blog/"]')->each(function (Crawler $node) use ($links) {
                $href = $node->attr('href');
                if ($href && ! str_ends_with($href, '/blog') && ! str_ends_with($href, '/blog/')) {
                    $links->push($this->normalizeUrl($href));
                }
            });

        } catch (\Exception $e) {
            // Ignore
        }

        return $links->unique();
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

        // Handle Matt's date format: "May 5, 2025"
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

        // Remove header elements that are not part of content
        $content = preg_replace('/<header\b[^>]*>.*?<\/header>/mi', '', $content);

        // Remove social sharing buttons and subscription forms
        $content = preg_replace('/<div[^>]*class="[^"]*share[^"]*"[^>]*>.*?<\/div>/msi', '', $content);
        $content = preg_replace('/<form[^>]*>.*?<\/form>/msi', '', $content);

        // Remove newsletter signup sections
        $content = preg_replace('/<div[^>]*class="[^"]*newsletter[^"]*"[^>]*>.*?<\/div>/msi', '', $content);
        $content = preg_replace('/<div[^>]*class="[^"]*subscribe[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove author bio sections
        $content = preg_replace('/<div[^>]*class="[^"]*author[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}