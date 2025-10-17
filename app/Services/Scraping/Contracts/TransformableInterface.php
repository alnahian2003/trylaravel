<?php

namespace App\Services\Scraping\Contracts;

use App\Models\Post;
use App\Services\Scraping\Core\ScrapedData;

interface TransformableInterface
{
    public function transform(ScrapedData $data): array;
    
    public function createPost(ScrapedData $data): Post;
    
    public function updatePost(Post $post, ScrapedData $data): Post;
}