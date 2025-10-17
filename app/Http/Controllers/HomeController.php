<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = Post::query()
            ->published()
            ->with([])
            ->latest('published_at')
            ->take(12)
            ->get()
            ->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'type' => [
                        'value' => $post->type->value,
                        'label' => $post->getTypeLabel(),
                        'icon' => $post->getTypeIcon(),
                        'color' => $post->getTypeColor(),
                    ],
                    'author' => [
                        'name' => $post->author_name,
                        'avatar' => $post->author_avatar,
                    ],
                    'published_at' => $post->published_at?->diffForHumans(),
                    'views_count' => $post->views_count,
                    'likes_count' => $post->likes_count,
                    'duration' => $post->formatted_duration,
                    'tags' => $post->tags,
                    'featured_image' => $post->featured_image,
                    'meta' => $post->meta,
                ];
            });

        $stats = [
            'total_posts' => Post::published()->count(),
            'posts_by_type' => [
                'posts' => Post::posts()->published()->count(),
                'videos' => Post::videos()->published()->count(),
                'podcasts' => Post::podcasts()->published()->count(),
            ],
            'trending_tags' => $this->getTrendingTags(),
        ];

        return Inertia::render('Home/Index', [
            'posts' => $posts,
            'stats' => $stats,
            'filters' => [
                'types' => PostType::options(),
            ],
        ]);
    }

    private function getTrendingTags(): array
    {
        $allTags = Post::published()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn ($count, $tag) => [
                'name' => $tag,
                'count' => $count,
            ])
            ->values()
            ->toArray();

        return $allTags;
    }
}
