<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to the OAuth provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the OAuth callback.
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }

        $user = $this->findOrCreateUser($socialiteUser, $provider);

        Auth::login($user, true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Validate the OAuth provider.
     */
    private function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['github', 'google'])) {
            abort(404);
        }
    }

    /**
     * Find or create a user based on the OAuth provider data.
     */
    private function findOrCreateUser(object $socialiteUser, string $provider): User
    {
        // First, try to find user by provider ID
        $user = User::where($provider . '_id', $socialiteUser->getId())->first();

        if ($user) {
            return $user;
        }

        // Then try to find by email
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if ($user) {
            // Link the provider to existing user
            $user->update([
                $provider . '_id' => $socialiteUser->getId(),
            ]);
            return $user;
        }

        // Create new user
        $user = User::create([
            'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'User',
            'email' => $socialiteUser->getEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(24)),
            $provider . '_id' => $socialiteUser->getId(),
        ]);

        event(new Registered($user));

        return $user;
    }
}
