<?php

use App\Enums\PostStatus;
use App\Enums\PostType;
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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->enum('type', PostType::values())->default(PostType::POST->value);
            $table->enum('status', PostStatus::values())->default(PostStatus::DRAFT->value);
            $table->string('featured_image')->nullable();
            $table->json('meta')->nullable(); // For type-specific data
            $table->string('source_url')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_avatar')->nullable();
            $table->integer('duration')->nullable(); // In seconds for video/podcast
            $table->string('file_url')->nullable(); // For video/podcast files
            $table->string('file_size')->nullable(); // File size in bytes
            $table->string('file_type')->nullable(); // MIME type
            $table->timestamp('published_at')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('categories')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['published_at', 'status']);
            $table->index('views_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
