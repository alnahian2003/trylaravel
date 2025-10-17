<?php

namespace App\Services\Scraping\Contracts;

use App\Services\Scraping\Core\ScrapedData;
use Illuminate\Support\Collection;

interface ScrapableInterface
{
    public function scrapeArticles(int $maxPages = 1): Collection;
    
    public function scrapeArticle(string $url): ScrapedData;
    
    public function getSiteKey(): string;
    
    public function getBaseUrl(): string;
    
    public function getConfiguration(): array;
}