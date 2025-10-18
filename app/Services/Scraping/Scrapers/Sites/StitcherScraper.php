<?php

namespace App\Services\Scraping\Scrapers\Sites;

use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class StitcherScraper extends AbstractScraper
{
    public function getSiteKey(): string
    {
        return 'stitcher';
    }

    public function getBaseUrl(): string
    {
        return 'https://stitcher.io';
    }

    protected function getArticlesListUrl(): string
    {
        return 'https://stitcher.io/';
    }

    protected function extractTitle(Crawler $crawler): string
    {
        $titleSelectors = [
            'h1',
            'meta[property="og:title"]',
            'meta[name="title"]',
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
                        $text = str_replace(' - stitcher.io', '', $text);
                        $text = str_replace('stitcher.io - ', '', $text);
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
            '.wrapper article',
            'main article',
            '[role="main"]',
            '.blog-content',
            '.post-content',
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

        // Default author for stitcher.io
        return 'Brent Roose';
    }

    protected function extractAuthorAvatar(Crawler $crawler): ?string
    {
        try {
            $avatarSelectors = [
                '.author-avatar img',
                '.author img',
                'img[alt*="Brent"]',
                'img[src*="avatar"]',
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

            // Extract tags from URL structure or content patterns
            $url = $crawler->getUri();
            if ($url && preg_match('/\/blog\/([^\/]+)/', $url, $matches)) {
                $urlSlug = $matches[1];
                // Common PHP-related topics from stitcher.io
                $phpTopics = ['php', 'laravel', 'symfony', 'performance', 'types', 'generics'];
                foreach ($phpTopics as $topic) {
                    if (str_contains($urlSlug, $topic)) {
                        $tags[] = ucfirst($topic);
                    }
                }
            }

            // Default tag for stitcher.io content
            if (empty($tags)) {
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
            ];

            foreach ($categorySelectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$categories) {
                    $text = $this->cleanText($node->text());
                    if (! empty($text) && ! in_array($text, ['Home', 'Blog'])) {
                        $categories[] = $text;
                    }
                });
            }

            // Default category for stitcher.io
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
            '.featured-image img',
            'article img:first-of-type',
            '.post-image img',
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
        // Stitcher.io doesn't seem to have pagination on the homepage
        // All articles are listed on the main page
        return $baseUrl;
    }



    private function parseDateFromText(string $dateText): ?string
    {
        // Handle various date formats
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

        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}