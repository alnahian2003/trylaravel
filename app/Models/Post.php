<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Enums\PostType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'type',
        'status',
        'featured_image',
        'meta',
        'source_url',
        'author_name',
        'author_email',
        'author_avatar',
        'duration',
        'file_url',
        'file_size',
        'file_type',
        'published_at',
        'views_count',
        'likes_count',
        'tags',
        'categories',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'meta' => 'array',
            'tags' => 'array',
            'categories' => 'array',
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'duration' => 'integer',
        ];
    }

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::PUBLISHED)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeOfType(Builder $query, PostType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopePosts(Builder $query): Builder
    {
        return $query->where('type', PostType::POST);
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', PostType::VIDEO);
    }

    public function scopePodcasts(Builder $query): Builder
    {
        return $query->where('type', PostType::PODCAST);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('views_count', 'desc');
    }

    // Accessors
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === PostStatus::PUBLISHED &&
               $this->published_at &&
               $this->published_at->isPast();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Mutators
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = $value;
        
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // Methods
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    public function isVideo(): bool
    {
        return $this->type === PostType::VIDEO;
    }

    public function isPodcast(): bool
    {
        return $this->type === PostType::PODCAST;
    }

    public function isPost(): bool
    {
        return $this->type === PostType::POST;
    }

    public function hasFile(): bool
    {
        return !empty($this->file_url);
    }

    public function publish(): void
    {
        $this->update([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => PostStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    // Enum helper methods
    public function getTypeLabel(): string
    {
        return $this->type->label();
    }

    public function getTypeIcon(): string
    {
        return $this->type->icon();
    }

    public function getTypeColor(): string
    {
        return $this->type->color();
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function getStatusColor(): string
    {
        return $this->status->color();
    }

    public function supportsFiles(): bool
    {
        return $this->type->supportsFiles();
    }

    public function supportsDuration(): bool
    {
        return $this->type->supportsDuration();
    }

    public function getAllowedFileTypes(): array
    {
        return $this->type->allowedFileTypes();
    }

    public function getDefaultMeta(): array
    {
        return $this->type->defaultMeta();
    }
}
