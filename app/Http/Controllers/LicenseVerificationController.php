<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LicenseVerificationController extends Controller
{
    public function index()
    {
        $users = User::where('driving_license_status', 'pending')
            ->whereNotNull('driving_license_front')
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/LicenseVerifications', [
            'users' => $users
        ]);
    }

    public function show(User $user)
    {
        return Inertia::render('Settings/LicenseVerification', [
            'user' => $user->only([
                'id',
                'name',
                'email',
                'driving_license_number',
                'driving_license_expiry',
                'driving_license_front',
                'driving_license_back',
                'driving_license_status',
                'driving_license_verified_at',
                'driving_license_rejection_reason'
            ])
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'driving_license_number' => 'required|string|max:50',
            'driving_license_expiry' => 'required|date|after:today',
            'driving_license_front' => 'required|image|max:5120',
            'driving_license_back' => 'required|image|max:5120'
        ]);

        $user = auth()->user();

        if ($request->hasFile('driving_license_front')) {
            if ($user->driving_license_front) {
                Storage::delete($user->driving_license_front);
            }
            $frontPath = $request->file('driving_license_front')->store('licenses', 'public');
        }

        if ($request->hasFile('driving_license_back')) {
            if ($user->driving_license_back) {
                Storage::delete($user->driving_license_back);
            }
            $backPath = $request->file('driving_license_back')->store('licenses', 'public');
        }

        $user->update([
            'driving_license_number' => $request->driving_license_number,
            'driving_license_expiry' => $request->driving_license_expiry,
            'driving_license_front' => $frontPath ?? $user->driving_license_front,
            'driving_license_back' => $backPath ?? $user->driving_license_back,
            'driving_license_status' => 'pending',
            'driving_license_verified_at' => null,
            'driving_license_rejection_reason' => null
        ]);

        return redirect()->route('settings.driver-license')
            ->with('success', 'Votre permis de conduire a été soumis pour vérification.');
    }

    public function verify(Request $request, User $user)
    {
        Gate::authorizee('admin');

        $request->validate([
            'status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500'
        ]);

        $user->update([
            'driving_license_status' => $request->status,
            'driving_license_verified_at' => $request->status === 'verified' ? now() : null,
            'driving_license_rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
            'is_verified' => $request->status === 'verified'
        ]);

        return redirect()->route('admin.license-verifications')
            ->with('success', 'Le statut du permis a été mis à jour.');
    }

    public function checkBeforeRental(User $user)
    {
        if (!$user->driving_license_number || !$user->driving_license_expiry) {
            return [
                'valid' => false,
                'message' => 'Vous devez fournir les informations de votre permis de conduire.'
            ];
        }

        if ($user->driving_license_expiry < now()) {
            return [
                'valid' => false,
                'message' => 'Votre permis de conduire a expiré.'
            ];
        }

        if ($user->driving_license_status === 'rejected') {
            return [
                'valid' => false,
                'message' => 'Votre permis de conduire a été rejeté. Raison: ' . $user->driving_license_rejection_reason
            ];
        }

        if ($user->driving_license_status === 'pending') {
            return [
                'valid' => true,
                'warning' => 'Votre permis est en cours de vérification. La location pourrait être annulée si la vérification échoue.'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Permis de conduire vérifié'
        ];
    }
}
