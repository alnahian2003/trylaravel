<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Source extends Model
{
    /** @use HasFactory<\Database\Factories\SourceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'feed_url',
        'description',
        'favicon_url',
        'is_active',
        'last_fetched_at',
        'posts_count',
        'metadata',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
