<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddSourceRequest;
use App\Models\Source;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SourceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $sources = $user->sources()->orderBy('created_at', 'desc')->get();

        // Calculate stats
        $stats = [
            'active_sources' => $sources->where('is_active', true)->count(),
            'total_articles' => $sources->sum('posts_count') ?: 0,
            'last_updated' => $sources->whereNotNull('last_fetched_at')
                ->max('last_fetched_at')?->diffForHumans() ?? 'Never',
        ];

        return Inertia::render('Sources/Index', [
            'sources' => $sources,
            'stats' => $stats,
        ]);
    }

    public function store(AddSourceRequest $request)
    {
        $feedUrl = $request->validated()['url'];

        // Try to extract domain for name
        $parsedUrl = parse_url($feedUrl);
        $domain = $parsedUrl['host'] ?? 'Unknown Source';
        $name = ucwords(str_replace(['www.', '.com', '.net', '.org'], '', $domain));

        Auth::user()->sources()->create([
            'name' => $name,
            'url' => $feedUrl, // For now, same as feed_url
            'feed_url' => $feedUrl,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Source added successfully!');
    }

    public function destroy(Source $source)
    {
        // Ensure user owns this source
        if ($source->user_id !== Auth::id()) {
            abort(403);
        }

        $source->delete();

        return redirect()->back()->with('success', 'Source removed successfully!');
    }
}
