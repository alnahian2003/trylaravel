<?php

namespace App\Services\Scraping\Core;

class ScrapedData
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly string $url,
        public readonly string $excerpt = '',
        public readonly string $author = '',
        public readonly ?string $authorAvatar = null,
        public readonly ?string $authorEmail = null,
        public readonly ?string $publishedAt = null,
        public readonly array $tags = [],
        public readonly array $categories = [],
        public readonly ?string $featuredImage = null,
        public readonly array $meta = [],
        public readonly string $sourceUrl = '',
        public readonly int $readingTime = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'url' => $this->url,
            'excerpt' => $this->excerpt,
            'author' => $this->author,
            'author_avatar' => $this->authorAvatar,
            'author_email' => $this->authorEmail,
            'published_at' => $this->publishedAt,
            'tags' => $this->tags,
            'categories' => $this->categories,
            'featured_image' => $this->featuredImage,
            'meta' => $this->meta,
            'source_url' => $this->sourceUrl,
            'reading_time' => $this->readingTime,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            url: $data['url'] ?? '',
            excerpt: $data['excerpt'] ?? '',
            author: $data['author'] ?? '',
            authorAvatar: $data['author_avatar'] ?? null,
            authorEmail: $data['author_email'] ?? null,
            publishedAt: $data['published_at'] ?? null,
            tags: $data['tags'] ?? [],
            categories: $data['categories'] ?? [],
            featuredImage: $data['featured_image'] ?? null,
            meta: $data['meta'] ?? [],
            sourceUrl: $data['source_url'] ?? '',
            readingTime: $data['reading_time'] ?? 0,
        );
    }
}