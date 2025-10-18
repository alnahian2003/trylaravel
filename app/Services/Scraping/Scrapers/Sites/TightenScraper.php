<?php

namespace App\Services\Scraping\Scrapers\Sites;

use App\Services\Scraping\Scrapers\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;

class TightenScraper extends AbstractScraper
{
    public function getSiteKey(): string
    {
        return 'tighten';
    }

    public function getBaseUrl(): string
    {
        return 'https://tighten.com';
    }

    protected function getArticlesListUrl(): string
    {
        return 'https://tighten.com/insights';
    }

    protected function extractTitle(Crawler $crawler): string
    {
        $titleSelectors = [
            'h1',
            '.font-serif.text-2xl',
            '.font-serif.text-4xl',
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
                        $text = str_replace(' | Tighten', '', $text);
                        $text = str_replace('Tighten - ', '', $text);
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
            '.prose',
            '.post-content',
            '.entry-content',
            '.article-content',
            'main .content',
            '[role="main"] article',
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
            '.line-clamp-3',
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
            '.font-mono.font-semibold',
        ];

        foreach ($authorSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $text = str_contains($selector, 'meta')
                        ? $element->attr('content')
                        : $element->text();

                    if (! empty($text)) {
                        // Clean author name from Tighten's format
                        $text = preg_replace('/^\d+\s+weeks?\s+ago\s*·\s*/', '', $text);
                        $text = preg_replace('/^.*·\s*/', '', $text);
                        $text = trim($text);
                        
                        if (! empty($text)) {
                            return $this->cleanText($text);
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Default author for Tighten
        return 'Tighten Team';
    }

    protected function extractAuthorAvatar(Crawler $crawler): ?string
    {
        try {
            $avatarSelectors = [
                '.author-avatar img',
                '.author img',
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
            ];

            foreach ($tagSelectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$tags) {
                    $text = $this->cleanText($node->text());
                    $text = ltrim($text, '#');
                    if (! empty($text) && ! in_array($text, ['Blog', 'Post', 'Article', 'Insights'])) {
                        $tags[] = $text;
                    }
                });
            }

            // Extract tags from content or URL patterns
            $url = $crawler->getUri();
            if ($url) {
                // Common Laravel/web dev topics from Tighten
                $techTopics = ['laravel', 'php', 'vue', 'javascript', 'css', 'livewire', 'inertia', 'react', 'tailwind', 'alpine'];
                foreach ($techTopics as $topic) {
                    if (str_contains(strtolower($url), $topic)) {
                        $tags[] = ucfirst($topic);
                    }
                }
            }

            // Default tags for Tighten content
            if (empty($tags)) {
                $tags[] = 'Laravel';
                $tags[] = 'Web Development';
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
                    if (! empty($text) && ! in_array($text, ['Home', 'Blog', 'Insights'])) {
                        $categories[] = $text;
                    }
                });
            }

            // Default category for Tighten
            if (empty($categories)) {
                $categories[] = 'Web Development';
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
            '.bg-cover[data-bg]',
        ];

        foreach ($imageSelectors as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $src = str_contains($selector, 'meta')
                        ? $element->attr('content')
                        : ($element->attr('src') ?? $element->attr('data-bg'));

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
        // Tighten uses pagination: /insights/2/, /insights/3/, etc.
        if ($page === 1) {
            return $baseUrl . '/insights';
        }
        
        return $baseUrl . '/insights/' . $page . '/';
    }

    protected function extractArticleLinks(Crawler $crawler): \Illuminate\Support\Collection
    {
        $links = collect();

        try {
            // Get all article links from the insights page, but exclude podcasts
            $crawler->filter('a[href*="/insights/"]')->each(function (Crawler $node) use ($links) {
                $href = $node->attr('href');
                if (! $href) {
                    return;
                }

                // Skip if it's the main insights page or pagination
                if ($href === '/insights' || $href === '/insights/' || preg_match('/\/insights\/\d+\/?$/', $href)) {
                    return;
                }

                // Check if this is an article (not a podcast)
                // Look for the article badge or absence of podcast badge
                $parentCard = $node->closest('.w-full');
                if ($parentCard->count() > 0) {
                    $badge = $parentCard->filter('.font-mono.font-bold.text-sm.uppercase')->first();
                    if ($badge->count() > 0) {
                        $badgeText = strtolower(trim($badge->text()));
                        // Only include if it's marked as "Article" or doesn't have "podcast"
                        if ($badgeText === 'article' || (! str_contains($badgeText, 'podcast'))) {
                            $links->push($this->normalizeUrl($href));
                        }
                    } else {
                        // If no badge found, assume it's an article
                        $links->push($this->normalizeUrl($href));
                    }
                }
            });

            // Also check for article links in the featured article section
            $crawler->filter('a[href*="/insights/"][aria-label]')->each(function (Crawler $node) use ($links) {
                $href = $node->attr('href');
                if ($href && ! str_contains($href, 'podcast')) {
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

        // Handle relative dates from Tighten: "1 week ago", "2 weeks ago", etc.
        if (preg_match('/(\d+)\s+weeks?\s+ago/', $dateText, $matches)) {
            try {
                $weeksAgo = (int) $matches[1];
                return \Carbon\Carbon::now()->subWeeks($weeksAgo)->toISOString();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // Handle date formats like "Jul 25", "Aug 12"
        if (preg_match('/(\w{3})\s+(\d{1,2})/', $dateText, $matches)) {
            try {
                $month = $matches[1];
                $day = $matches[2];
                $year = date('Y'); // Current year
                
                return \Carbon\Carbon::parse("$month $day, $year")->toISOString();
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

        // Remove forms (contact/newsletter forms)
        $content = preg_replace('/<form\b[^>]*>.*?<\/form>/msi', '', $content);

        // Remove search boxes
        $content = preg_replace('/<div[^>]*class="[^"]*search[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove social sharing buttons
        $content = preg_replace('/<div[^>]*class="[^"]*share[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove newsletter signup sections
        $content = preg_replace('/<div[^>]*class="[^"]*newsletter[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove author bio sections
        $content = preg_replace('/<div[^>]*class="[^"]*author[^"]*"[^>]*>.*?<\/div>/msi', '', $content);

        // Remove sidebar content
        $content = preg_replace('/<aside\b[^>]*>.*?<\/aside>/msi', '', $content);

        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}