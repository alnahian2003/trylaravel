<?php

namespace Tests\Unit;

use App\Enums\PostType;
use App\Http\Controllers\HomeController;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    private HomeController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $rankingService = $this->app->make(\App\Services\ContentRankingService::class);
        $this->controller = new HomeController($rankingService);
    }

    public function test_index_returns_inertia_response(): void
    {
        $request = Request::create('/');
        $response = $this->controller->index($request);

        $this->assertInstanceOf(\Inertia\Response::class, $response);

        // Use reflection to access protected properties
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Home/Index', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('posts', $props);
        $this->assertArrayHasKey('stats', $props);
        $this->assertArrayHasKey('filters', $props);
    }

    public function test_filters_contain_post_type_options(): void
    {
        $request = Request::create('/');
        $response = $this->controller->index($request);

        $reflection = new \ReflectionClass($response);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);
        $props = $propsProperty->getValue($response);

        $filters = $props['filters'];
        $this->assertArrayHasKey('types', $filters);
        $this->assertEquals(PostType::options(), $filters['types']);
    }

    public function test_get_trending_tags_returns_correct_format(): void
    {
        // Create posts with various tags
        Post::factory()->published()->create(['tags' => ['Laravel', 'PHP']]);
        Post::factory()->published()->create(['tags' => ['Laravel', 'Vue']]);
        Post::factory()->published()->create(['tags' => ['PHP', 'Testing']]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTrendingTags');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check structure of first trending tag
        if (! empty($result)) {
            $this->assertArrayHasKey('name', $result[0]);
            $this->assertArrayHasKey('count', $result[0]);
            $this->assertIsString($result[0]['name']);
            $this->assertIsInt($result[0]['count']);
        }
    }

    public function test_trending_tags_are_sorted_by_count(): void
    {
        // Create posts with tags to establish a clear ranking
        Post::factory()->count(3)->published()->create(['tags' => ['Laravel']]); // Most popular
        Post::factory()->count(2)->published()->create(['tags' => ['PHP']]);     // Second
        Post::factory()->count(1)->published()->create(['tags' => ['Vue']]);     // Third

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTrendingTags');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertEquals('Laravel', $result[0]['name']);
        $this->assertEquals(3, $result[0]['count']);
        $this->assertEquals('PHP', $result[1]['name']);
        $this->assertEquals(2, $result[1]['count']);
        $this->assertEquals('Vue', $result[2]['name']);
        $this->assertEquals(1, $result[2]['count']);
    }

    public function test_trending_tags_limits_to_ten_results(): void
    {
        // Create posts with 15 different tags
        for ($i = 1; $i <= 15; $i++) {
            Post::factory()->published()->create(['tags' => ["Tag{$i}"]]);
        }

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTrendingTags');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertCount(10, $result);
    }

    public function test_trending_tags_handles_empty_tags(): void
    {
        // Create posts without tags
        Post::factory()->count(3)->published()->create(['tags' => null]);
        Post::factory()->count(2)->published()->create(['tags' => []]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTrendingTags');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
