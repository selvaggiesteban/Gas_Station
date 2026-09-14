<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google for authentication
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle Google callback
     */
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName() ?? explode('@', $socialUser->getEmail())[0],
                    'provider' => 'google',
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)) // Only used if creating new
                ]
            );

            if (!$user->wasRecentlyCreated) {
                // Ensure provider and provider_id are updated even if user existed by email
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $socialUser->getId(),
                ]);
            } else {
                $user->assignRole('Student');
            }

            Auth::login($user);
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            \Log::error('Google login error', ['error' => $e->getMessage()]);
            return redirect()->route('login')
                ->with('error', 'Error al iniciar sesión con Google.');
        }
    }

}
