<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Http\Requests\ReportPostRequest;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Home/Index', [
            'posts' => Inertia::scroll(fn () => Post::query()
                ->published()
                ->latest('published_at')
                ->paginate(12)
                ->through(function (Post $post) {
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
                })
            ),
            'stats' => Inertia::defer(fn () => [
                'total_posts' => Post::published()->count(),
                'posts_by_type' => [
                    'posts' => Post::posts()->published()->count(),
                    'videos' => Post::videos()->published()->count(),
                    'podcasts' => Post::podcasts()->published()->count(),
                ],
                'trending_tags' => $this->getTrendingTags(),
            ]),
            'filters' => [
                'types' => PostType::options(),
            ],
        ]);
    }

    public function show(Post $post): Response
    {
        if (! $post->is_published) {
            abort(404);
        }

        $post->incrementViews();

        // Get previous post (older)
        $previousPost = Post::query()
            ->published()
            ->where('published_at', '<', $post->published_at)
            ->orderBy('published_at', 'desc')
            ->first();

        // Get next post (newer)
        $nextPost = Post::query()
            ->published()
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at', 'asc')
            ->first();

        return Inertia::render('Posts/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'type' => [
                    'value' => $post->type->value,
                    'label' => $post->getTypeLabel(),
                    'icon' => $post->getTypeIcon(),
                    'color' => $post->getTypeColor(),
                ],
                'status' => [
                    'value' => $post->status->value,
                    'label' => $post->getStatusLabel(),
                    'color' => $post->getStatusColor(),
                ],
                'author' => [
                    'name' => $post->author_name,
                    'email' => $post->author_email,
                    'avatar' => $post->author_avatar,
                ],
                'published_at' => $post->published_at?->diffForHumans(),
                'formatted_published_at' => $post->published_at?->format('F j, Y'),
                'views_count' => $post->views_count,
                'likes_count' => $post->likes_count,
                'duration' => $post->formatted_duration,
                'tags' => $post->tags,
                'categories' => $post->categories,
                'featured_image' => $post->featured_image,
                'meta' => $post->meta,
                'source_url' => $post->source_url,
                'file_url' => $post->file_url,
                'file_size' => $post->formatted_file_size,
                'file_type' => $post->file_type,
                // User interactions
                'is_liked' => $post->isLikedBy(auth()->user()),
                'is_bookmarked' => $post->isBookmarkedBy(auth()->user()),
            ],
            'relatedPosts' => Post::query()
                ->published()
                ->where('id', '!=', $post->id)
                ->where('type', $post->type)
                ->latest('published_at')
                ->limit(3)
                ->get()
                ->map(function (Post $relatedPost) {
                    return [
                        'id' => $relatedPost->id,
                        'title' => $relatedPost->title,
                        'slug' => $relatedPost->slug,
                        'excerpt' => $relatedPost->excerpt,
                        'type' => [
                            'value' => $relatedPost->type->value,
                            'label' => $relatedPost->getTypeLabel(),
                            'icon' => $relatedPost->getTypeIcon(),
                            'color' => $relatedPost->getTypeColor(),
                        ],
                        'author' => [
                            'name' => $relatedPost->author_name,
                            'avatar' => $relatedPost->author_avatar,
                        ],
                        'published_at' => $relatedPost->published_at?->diffForHumans(),
                        'views_count' => $relatedPost->views_count,
                        'likes_count' => $relatedPost->likes_count,
                        'duration' => $relatedPost->formatted_duration,
                        'featured_image' => $relatedPost->featured_image,
                    ];
                }),
            'navigation' => [
                'previous' => $previousPost ? [
                    'title' => $previousPost->title,
                    'slug' => $previousPost->slug,
                    'type' => [
                        'label' => $previousPost->getTypeLabel(),
                        'icon' => $previousPost->getTypeIcon(),
                    ],
                ] : null,
                'next' => $nextPost ? [
                    'title' => $nextPost->title,
                    'slug' => $nextPost->slug,
                    'type' => [
                        'label' => $nextPost->getTypeLabel(),
                        'icon' => $nextPost->getTypeIcon(),
                    ],
                ] : null,
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

    public function toggleLike(Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $isLiked = $post->toggleLike($user);

        return response()->json([
            'is_liked' => $isLiked,
            'likes_count' => $post->fresh()->likes_count,
        ]);
    }

    public function toggleBookmark(Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $isBookmarked = $post->toggleBookmark($user);

        return response()->json([
            'is_bookmarked' => $isBookmarked,
        ]);
    }

    public function reportPost(ReportPostRequest $request, Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        // Check if user has already reported this post
        $existingReport = Report::query()
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->where('type', $request->type)
            ->exists();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this post for this reason.',
            ], 422);
        }

        Report::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'type' => $request->type,
            'description' => $request->description,
        ]);

        \Log::info('Post reported', [
            'post_id' => $post->id,
            'post_title' => $post->title,
            'reporter_id' => $user->id,
            'reporter_email' => $user->email,
            'report_type' => $request->type,
            'description' => $request->description,
            'reported_at' => now(),
        ]);

        return response()->json([
            'message' => 'Report submitted successfully. Thank you for helping keep our community safe.',
        ]);
    }
}
