<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Composite index for ranking algorithm queries
            $table->index(['status', 'published_at', 'views_count'], 'posts_ranking_composite');
            
            // Index for source URL filtering
            $table->index('source_url', 'posts_source_url_index');
            
            // Index for engagement metrics
            $table->index(['likes_count', 'views_count'], 'posts_engagement_index');
            
            // Index for recency filtering
            $table->index(['published_at', 'status'], 'posts_published_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_ranking_composite');
            $table->dropIndex('posts_source_url_index');
            $table->dropIndex('posts_engagement_index');
            $table->dropIndex('posts_published_status_index');
        });
    }
};
