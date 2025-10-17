<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlashMessageTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function login_shows_success_flash_message(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success', 'Welcome back! You have been successfully logged in.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function logout_shows_success_flash_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/logout');

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'You have been successfully logged out. See you next time!');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registration_shows_success_flash_message(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success', 'Welcome to our platform! Your account has been created successfully.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function flash_messages_are_shared_via_inertia(): void
    {
        $user = User::factory()->create();

        // First, create a flash message by logging out
        $this->actingAs($user)->post('/logout');

        // Then visit the home page and check if flash message is available in Inertia props
        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page->has('flash')
            ->where('flash.success', 'You have been successfully logged out. See you next time!')
        );
    }
}
