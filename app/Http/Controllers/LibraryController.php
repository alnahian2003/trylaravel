<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $selectedCategory = $request->get('category');
        $sortBy = $request->get('sort', 'newest');

        // Single optimized query with joins for O(1) complexity
        $query = Post::select([
            'posts.*',
            'post_bookmarks.created_at as bookmarked_at',
        ])
            ->join('post_bookmarks', 'posts.id', '=', 'post_bookmarks.post_id')
            ->where('post_bookmarks.user_id', $user->id)
            ->where('posts.status', \App\Enums\PostStatus::PUBLISHED)
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now());

        // Apply category filter if specified (database-agnostic approach)
        if ($selectedCategory) {
            $query->where(function ($q) use ($selectedCategory) {
                $q->where('posts.categories', 'LIKE', '%"'.$selectedCategory.'"%');
            });
        }

        // Apply sorting
        $query = $this->applySorting($query, $sortBy);

        // Get category counts in a single query using DB aggregation
        $categoryCounts = $this->getCategoryCounts($user);

        return Inertia::render('Library/Index', [
            'bookmarks' => Inertia::scroll(fn () => $query->paginate(12)
                ->appends($request->query())
            ),
            'categoryCounts' => $categoryCounts,
            'totalBookmarks' => $this->getTotalBookmarks($user),
            'selectedCategory' => $selectedCategory,
            'selectedSort' => $sortBy,
        ]);
    }

    private function getCategoryCounts($user): array
    {
        // Database-agnostic approach: get all categories and process in PHP
        $bookmarkedPosts = Post::select('categories')
            ->join('post_bookmarks', 'posts.id', '=', 'post_bookmarks.post_id')
            ->where('post_bookmarks.user_id', $user->id)
            ->where('posts.status', \App\Enums\PostStatus::PUBLISHED)
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now())
            ->whereNotNull('posts.categories')
            ->pluck('categories');

        $counts = [];
        foreach ($bookmarkedPosts as $categories) {
            // Categories are already cast to array by the Post model
            if (is_array($categories)) {
                foreach ($categories as $category) {
                    if ($category && is_string($category)) {
                        $counts[$category] = ($counts[$category] ?? 0) + 1;
                    }
                }
            }
        }

        // Sort by count descending
        arsort($counts);

        return $counts;
    }

    private function getTotalBookmarks($user): int
    {
        return Post::join('post_bookmarks', 'posts.id', '=', 'post_bookmarks.post_id')
            ->where('post_bookmarks.user_id', $user->id)
            ->where('posts.status', \App\Enums\PostStatus::PUBLISHED)
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now())
            ->count();
    }

    private function applySorting($query, string $sortBy)
    {
        return match ($sortBy) {
            'newest' => $query->orderBy('post_bookmarks.created_at', 'desc'),
            'oldest' => $query->orderBy('post_bookmarks.created_at', 'asc'),
            'most_read' => $query->orderBy('posts.views_count', 'desc')
                ->orderBy('post_bookmarks.created_at', 'desc'),
            'alphabetical' => $query->orderBy('posts.title', 'asc'),
            default => $query->orderBy('post_bookmarks.created_at', 'desc'),
        };
    }
}
