<?php

namespace Tests\Unit;

use App\Enums\PostStatus;
use App\Http\Controllers\HomeController;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeControllerShowTest extends TestCase
{
    use RefreshDatabase;

    private HomeController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $rankingService = $this->app->make(\App\Services\ContentRankingService::class);
        $this->controller = new HomeController($rankingService);
    }

    #[Test]
    public function show_method_returns_inertia_response(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->controller->show($post);

        $this->assertInstanceOf(\Inertia\Response::class, $response);
    }

    #[Test]
    public function show_method_increments_view_count(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'views_count' => 10,
        ]);

        $originalViewCount = $post->views_count;

        $this->controller->show($post);

        $post->refresh();
        $this->assertEquals($originalViewCount + 1, $post->views_count);
    }

    #[Test]
    public function show_method_calls_increment_views(): void
    {
        $post = Post::factory()->create([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'views_count' => 100,
        ]);

        $this->controller->show($post);

        $post->refresh();
        $this->assertEquals(101, $post->views_count);
    }
}
