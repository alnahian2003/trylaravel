<?php

namespace App\Services\Scraping\Scrapers\Sites;

use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class SpatieScraper extends AbstractScraper
{
    public function getSiteKey(): string
    {
        return 'spatie';
    }

    public function getBaseUrl(): string
    {
        return 'https://spatie.be';
    }

    protected function getArticlesListUrl(): string
    {
        return 'https://spatie.be/blog';
    }

    protected function extractTitle(Crawler $crawler): string
    {
        $titleSelectors = [
            'h1',
            'meta[property="og:title"]',
            'title',
        ];

        foreach ($titleSelectors as $selector) {
            try {
                $title = $crawler->filter($selector)->first();
                if ($title->count() > 0) {
                    $text = $selector === 'meta[property="og:title"]'
                        ? $title->attr('content')
                        : $title->text();

                    if (! empty($text)) {
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
            '.markup',
            '.content-markup',
            'article .wrapper-sm',
            '[class*="content"]',
            'main article',
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
            '.excerpt',
            '.teaser',
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
            '.leading-none.text-oss-royal-blue.font-bold',
            '.font-bold',
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

                    if (! empty($text) && ! str_contains(strtolower($text), 'continue reading')) {
                        return $this->cleanText($text);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return 'Spatie';
    }

    protected function extractAuthorAvatar(Crawler $crawler): ?string
    {
        try {
            $avatar = $crawler->filter('img[src*="gravatar.com"]')->first();
            if ($avatar->count() > 0) {
                return $avatar->attr('src');
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
            '.text-base time',
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
            // Look for tags in various formats
            $tagSelectors = [
                '.font-semibold.text-oss-royal-blue',
                '.lowercase.font-bold li',
                '.font-bold li',
                '[class*="tag"]',
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

            // Also check for hashtag-style tags in text content
            $content = $crawler->text();
            if (preg_match_all('/#([a-zA-Z]+)/', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    if (! in_array($match, $tags)) {
                        $tags[] = $match;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return array_unique(array_filter($tags));
    }

    protected function extractReadingTime(Crawler $crawler): int
    {
        try {
            // Try to find reading time indicators
            $readingTimeSelectors = [
                '[class*="reading-time"]',
                '[class*="read-time"]',
                '.meta [class*="time"]',
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

    protected function extractFeaturedImage(Crawler $crawler): ?string
    {
        $imageSelectors = [
            'meta[property="og:image"]',
            '.featured-image img',
            'article img:first-of-type',
            '.aspect-square img',
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

    protected function getPageUrl(string $baseUrl, int $page): string
    {
        // Spatie blog uses query parameter for pagination
        return $baseUrl.'?page='.$page;
    }

    private function parseDateFromText(string $dateText): ?string
    {
        // Handle various date formats
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

        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}
