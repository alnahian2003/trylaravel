<?php

namespace App\Enums;

enum PostType: string
{
    case POST = 'post';
    case VIDEO = 'video';
    case PODCAST = 'podcast';

    /**
     * Get the display name for the post type.
     */
    public function label(): string
    {
        return match ($this) {
            self::POST => 'Blog Post',
            self::VIDEO => 'Video',
            self::PODCAST => 'Podcast',
        };
    }

    /**
     * Get the icon for the post type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::POST => 'document-text',
            self::VIDEO => 'play-circle',
            self::PODCAST => 'microphone',
        };
    }

    /**
     * Get the color class for the post type.
     */
    public function color(): string
    {
        return match ($this) {
            self::POST => 'blue',
            self::VIDEO => 'red',
            self::PODCAST => 'purple',
        };
    }

    /**
     * Check if the post type supports files.
     */
    public function supportsFiles(): bool
    {
        return match ($this) {
            self::POST => false,
            self::VIDEO, self::PODCAST => true,
        };
    }

    /**
     * Check if the post type supports duration.
     */
    public function supportsDuration(): bool
    {
        return match ($this) {
            self::POST => false,
            self::VIDEO, self::PODCAST => true,
        };
    }

    /**
     * Get the expected file types for this post type.
     */
    public function allowedFileTypes(): array
    {
        return match ($this) {
            self::POST => [],
            self::VIDEO => ['video/mp4', 'video/webm', 'video/avi', 'video/mov'],
            self::PODCAST => ['audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a'],
        };
    }

    /**
     * Get the default meta structure for this post type.
     */
    public function defaultMeta(): array
    {
        return match ($this) {
            self::POST => [
                'reading_time' => null,
                'word_count' => null,
                'table_of_contents' => [],
            ],
            self::VIDEO => [
                'resolution' => null,
                'codec' => null,
                'thumbnail_url' => null,
                'chapters' => [],
            ],
            self::PODCAST => [
                'episode_number' => null,
                'season' => null,
                'transcript_url' => null,
                'show_notes' => null,
            ],
        };
    }

    /**
     * Get all post type values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all post types as options for select inputs.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [
            $type->value => $type->label()
        ])->toArray();
    }

    /**
     * Create a PostType instance from a string value.
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Check if a value is a valid post type.
     */
    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * Get a random post type.
     */
    public static function random(): self
    {
        $cases = self::cases();
        return $cases[array_rand($cases)];
    }

    /**
     * Get content types that support media files.
     */
    public static function mediaTypes(): array
    {
        return [self::VIDEO, self::PODCAST];
    }

    /**
     * Get content types that are text-based.
     */
    public static function textTypes(): array
    {
        return [self::POST];
    }
}