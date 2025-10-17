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
        Schema::table('sources', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'feed_url']);
            $table->dropIndex(['user_id', 'is_active']);
            
            $table->foreignId('user_id')->nullable()->change();
            
            $table->index(['is_active']);
            $table->index(['url']);
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['url']);
            
            $table->foreignId('user_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
            
            $table->unique(['user_id', 'feed_url']);
            $table->index(['user_id', 'is_active']);
        });
    }
};
