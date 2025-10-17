<?php

namespace App\Services\Scraping\Scrapers\Sites;

use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class CodecourseScraper extends AbstractScraper
{
    public function getSiteKey(): string
    {
        return 'codecourse';
    }

    public function getBaseUrl(): string
    {
        return 'https://codecourse.com';
    }

    protected function getArticlesListUrl(): string
    {
        return 'https://codecourse.com/articles';
    }

    protected function extractTitle(Crawler $crawler): string
    {
        $titleSelectors = [
            'h1',
            'title',
            'meta[property="og:title"]',
        ];

        foreach ($titleSelectors as $selector) {
            try {
                $title = $crawler->filter($selector)->first();
                if ($title->count() > 0) {
                    $text = $selector === 'meta[property="og:title"]' 
                        ? $title->attr('content') 
                        : $title->text();
                    
                    if (!empty($text)) {
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
            '.prose',
            'article',
            '.content',
            '.article-content',
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
            '.teaser',
            '.excerpt',
        ];

        foreach ($excerptSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $text = str_contains($selector, 'meta') 
                        ? $element->attr('content') 
                        : $element->text();
                    
                    if (!empty($text)) {
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
            '.border-t .font-semibold.bg-gradient-to-r',
            'meta[name="author"]',
            '.author-name',
            '[rel="author"]',
        ];

        foreach ($authorSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $text = str_contains($selector, 'meta') 
                        ? $element->attr('content') 
                        : $element->text();
                    
                    if (!empty($text) && !str_contains(strtolower($text), 'back to')) {
                        return $this->cleanText($text);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return 'Alex Garrett-Smith';
    }

    protected function extractAuthorAvatar(Crawler $crawler): ?string
    {
        try {
            $avatar = $crawler->filter('img[src*="codecourse-avatars"]')->first();
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
            '.text-xs.uppercase.font-medium',
            'time[datetime]',
            'meta[property="article:published_time"]',
        ];

        foreach ($dateSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $dateText = str_contains($selector, 'meta') 
                        ? $element->attr('content')
                        : ($element->attr('datetime') ?? $element->text());
                    
                    if (!empty($dateText)) {
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
            $crawler->filter('.rounded-full')->each(function (Crawler $node) use (&$tags) {
                $text = $this->cleanText($node->text());
                if (!empty($text) && !in_array($text, ['Course', 'Article', 'Snippet'])) {
                    $tags[] = $text;
                }
            });
        } catch (\Exception $e) {
            // Ignore
        }

        return array_unique($tags);
    }

    protected function extractReadingTime(Crawler $crawler): int
    {
        try {
            $timeElements = $crawler->filter('.text-xs.uppercase.font-medium');
            
            foreach ($timeElements as $element) {
                $text = $element->textContent;
                if (preg_match('/(\d+)\s*minutes?\s*read/i', $text, $matches)) {
                    return (int) $matches[1];
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 0;
    }

    protected function extractFeaturedImage(Crawler $crawler): ?string
    {
        $imageSelectors = [
            'meta[property="og:image"]',
            '.featured-image img',
            'article img:first-of-type',
        ];

        foreach ($imageSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $src = str_contains($selector, 'meta') 
                        ? $element->attr('content') 
                        : $element->attr('src');
                    
                    if (!empty($src)) {
                        return $this->normalizeUrl($src);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    private function parseDateFromText(string $dateText): ?string
    {
        if (preg_match('/(\w+\s+\d{1,2}(?:st|nd|rd|th)?,?\s+\d{4})/', $dateText, $matches)) {
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
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
        
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }
}