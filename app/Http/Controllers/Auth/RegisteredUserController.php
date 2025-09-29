<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        $referralCode = $request->get('ref');
        $referrer = null;
        
        if ($referralCode) {
            $referrer = User::where('referral_code', strtoupper($referralCode))->first();
        }
        
        return Inertia::render('auth/Register', [
            'referralCode' => $referralCode,
            'referrer' => $referrer ? [
                'name' => $referrer->name,
                'avatar' => $referrer->avatar,
            ] : null,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_role' => 'required|string|in:locataire,proprietaire',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        $referrer = null;
        if ($request->referral_code) {
            $referrer = User::where('referral_code', strtoupper($request->referral_code))->first();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => $request->user_role,
            'is_owner' => $request->user_role === 'proprietaire',
            'referred_by' => $referrer?->id,
        ]);

        // Create referral tracking if there's a referrer
        if ($referrer && $referrer->id !== $user->id) {
            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $user->id,
                'referral_code' => $referrer->referral_code,
                'status' => 'pending',
            ]);

            // Mark as completed immediately for registration
            $referral->markAsCompleted('registration');
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
