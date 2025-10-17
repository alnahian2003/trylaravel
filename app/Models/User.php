<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'github_id',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function likedPosts()
    {
        return $this->hasManyThrough(Post::class, PostLike::class, 'user_id', 'id', 'id', 'post_id');
    }

    public function bookmarkedPosts()
    {
        return $this->hasManyThrough(Post::class, PostBookmark::class, 'user_id', 'id', 'id', 'post_id');
    }

    public function postLikes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function postBookmarks()
    {
        return $this->hasMany(PostBookmark::class);
    }
}
