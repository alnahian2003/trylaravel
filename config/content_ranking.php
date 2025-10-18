<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Ranking Algorithm Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the content ranking algorithm
    | used to determine post order for anonymous users.
    |
    */

    'weights' => [
        'source_authority' => env('RANKING_WEIGHT_SOURCE', 0.4),
        'recency' => env('RANKING_WEIGHT_RECENCY', 0.3),
        'engagement' => env('RANKING_WEIGHT_ENGAGEMENT', 0.3),
    ],

    'cache' => [
        'trending_ttl' => env('RANKING_CACHE_TRENDING_TTL', 300), // 5 minutes
        'hero_ttl' => env('RANKING_CACHE_HERO_TTL', 600), // 10 minutes
        'algorithm_ttl' => env('RANKING_CACHE_ALGORITHM_TTL', 1800), // 30 minutes
    ],

    'thresholds' => [
        'hero_content_score' => env('RANKING_HERO_THRESHOLD', 7.0),
        'trending_min_views' => env('RANKING_TRENDING_MIN_VIEWS', 10),
        'trending_min_likes' => env('RANKING_TRENDING_MIN_LIKES', 2),
        'trending_hours' => env('RANKING_TRENDING_HOURS', 24),
    ],

    'debug' => [
        'enabled' => env('RANKING_DEBUG', false),
        'show_scores' => env('RANKING_SHOW_SCORES', false),
    ],

];