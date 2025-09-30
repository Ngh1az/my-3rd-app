<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['Google' => 'Failed to authenticate with Google. Please try again.']);
        }
        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();
        if (! $user && $googleUser->getEmail()) {
            $user = User::where('email', $googleUser->getEmail())->first();
        }
        if (! $user) {
            $user = User::forcefill([
                'name' => $googleUser->getName() ?? $googleUser->getNickname(),
                'email' => $googleUser->getEmail(),
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'avatar' => $googleUser->getAvatar(),
                'password' => Hash::make(Str::random(32)),
            ]);
        } else {
            $user->update([
                'name' => $googleUser->getName() ?? $googleUser->getNickname(),
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        }
        Auth::login($user, true);
        return redirect()->intended('/dashboard')->with("success", "Logged in with Google successfully.");
    }
}
