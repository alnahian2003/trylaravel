<?php

namespace App\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get the display name for the post status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get the color class for the post status.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'yellow',
            self::PUBLISHED => 'green',
            self::ARCHIVED => 'gray',
        };
    }

    /**
     * Check if the status allows public viewing.
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if the status allows editing.
     */
    public function isEditable(): bool
    {
        return match ($this) {
            self::DRAFT, self::ARCHIVED => true,
            self::PUBLISHED => false,
        };
    }

    /**
     * Get all status values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all statuses as options for select inputs.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [
            $status->value => $status->label()
        ])->toArray();
    }

    /**
     * Create a PostStatus instance from a string value.
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Check if a value is a valid post status.
     */
    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
