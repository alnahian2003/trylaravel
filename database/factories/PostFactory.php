<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Enums\PostType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 8));
        $type = PostType::random();
        $publishedAt = fake()->optional(0.8)->dateTimeBetween('-1 year', 'now');

        return [
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . fake()->unique()->randomNumber(3),
            'excerpt' => fake()->paragraph(2),
            'content' => fake()->paragraphs(rand(3, 8), true),
            'type' => $type,
            'status' => $publishedAt ? PostStatus::PUBLISHED : fake()->randomElement([PostStatus::DRAFT, PostStatus::PUBLISHED]),
            'featured_image' => fake()->optional(0.7)->imageUrl(800, 600, 'technology'),
            'meta' => $this->generateMeta($type),
            'source_url' => fake()->optional(0.3)->url(),
            'author_name' => fake()->name(),
            'author_email' => fake()->email(),
            'author_avatar' => fake()->optional(0.6)->imageUrl(100, 100, 'people'),
            'duration' => $type->supportsDuration() ? fake()->numberBetween(120, 7200) : null,
            'file_url' => $type->supportsFiles() ? $this->generateFileUrl($type) : null,
            'file_size' => $type->supportsFiles() ? fake()->numberBetween(10485760, 536870912) : null, // 10MB - 512MB
            'file_type' => $this->generateFileType($type),
            'published_at' => $publishedAt,
            'views_count' => fake()->numberBetween(0, 10000),
            'likes_count' => fake()->numberBetween(0, 1000),
            'tags' => fake()->randomElements([
                'Laravel', 'PHP', 'Vue.js', 'JavaScript', 'CSS', 'HTML', 'MySQL', 'Redis',
                'Docker', 'AWS', 'API', 'Frontend', 'Backend', 'DevOps', 'Tutorial',
                'Tips', 'Best Practices', 'Performance', 'Security', 'Testing'
            ], rand(1, 5)),
            'categories' => fake()->randomElements([
                'Web Development', 'Tutorial', 'News', 'Opinion', 'Review',
                'Interview', 'Case Study', 'Tools', 'Framework', 'Database'
            ], rand(1, 3)),
        ];
    }

    /**
     * Generate type-specific metadata.
     */
    private function generateMeta(PostType $type): array
    {
        return match ($type) {
            PostType::VIDEO => [
                'resolution' => fake()->randomElement(['720p', '1080p', '4K']),
                'codec' => fake()->randomElement(['H.264', 'H.265', 'VP9']),
                'thumbnail_url' => fake()->imageUrl(1280, 720, 'technology'),
                'chapters' => fake()->optional(0.3)->randomElements([
                    ['title' => 'Introduction', 'start' => 0],
                    ['title' => 'Main Content', 'start' => 180],
                    ['title' => 'Conclusion', 'start' => 900],
                ]),
            ],
            PostType::PODCAST => [
                'episode_number' => fake()->optional(0.7)->numberBetween(1, 500),
                'season' => fake()->optional(0.5)->numberBetween(1, 10),
                'transcript_url' => fake()->optional(0.4)->url(),
                'show_notes' => fake()->optional(0.6)->paragraphs(3, true),
            ],
            PostType::POST => [
                'reading_time' => fake()->numberBetween(1, 15),
                'word_count' => fake()->numberBetween(500, 5000),
                'table_of_contents' => fake()->optional(0.3)->randomElements([
                    'Introduction',
                    'Getting Started',
                    'Advanced Features',
                    'Best Practices',
                    'Conclusion',
                ], rand(2, 5)),
            ],
        };
    }

    /**
     * Generate file URL based on type.
     */
    private function generateFileUrl(PostType $type): string
    {
        return match ($type) {
            PostType::VIDEO => 'https://example.com/videos/' . fake()->uuid() . '.mp4',
            PostType::PODCAST => 'https://example.com/podcasts/' . fake()->uuid() . '.mp3',
            default => fake()->url(),
        };
    }

    /**
     * Generate file type based on content type.
     */
    private function generateFileType(PostType $type): ?string
    {
        return match ($type) {
            PostType::VIDEO => fake()->randomElement($type->allowedFileTypes()),
            PostType::PODCAST => fake()->randomElement($type->allowedFileTypes()),
            default => null,
        };
    }

    /**
     * Create a published post.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Create a draft post.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Create a video post.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::VIDEO,
            'duration' => fake()->numberBetween(300, 3600), // 5 minutes to 1 hour
            'file_url' => 'https://example.com/videos/' . fake()->uuid() . '.mp4',
            'file_size' => fake()->numberBetween(52428800, 1073741824), // 50MB - 1GB
            'file_type' => 'video/mp4',
            'meta' => [
                'resolution' => fake()->randomElement(['720p', '1080p', '4K']),
                'codec' => 'H.264',
                'thumbnail_url' => fake()->imageUrl(1280, 720, 'technology'),
            ],
        ]);
    }

    /**
     * Create a podcast post.
     */
    public function podcast(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::PODCAST,
            'duration' => fake()->numberBetween(900, 7200), // 15 minutes to 2 hours
            'file_url' => 'https://example.com/podcasts/' . fake()->uuid() . '.mp3',
            'file_size' => fake()->numberBetween(20971520, 209715200), // 20MB - 200MB
            'file_type' => 'audio/mp3',
            'meta' => [
                'episode_number' => fake()->numberBetween(1, 100),
                'season' => fake()->numberBetween(1, 5),
                'show_notes' => fake()->paragraphs(3, true),
            ],
        ]);
    }

    /**
     * Create a regular blog post.
     */
    public function blogPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::POST,
            'duration' => null,
            'file_url' => null,
            'file_size' => null,
            'file_type' => null,
            'meta' => [
                'reading_time' => fake()->numberBetween(2, 12),
                'word_count' => fake()->numberBetween(800, 3000),
            ],
        ]);
    }

    /**
     * Create a popular post with high engagement.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => fake()->numberBetween(5000, 50000),
            'likes_count' => fake()->numberBetween(500, 5000),
        ]);
    }
}
