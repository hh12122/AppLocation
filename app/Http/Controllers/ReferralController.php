<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReferralController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get user's referral statistics
        $stats = $user->getReferralStats();

        // Get user's referrals with related data
        $referrals = $user->referrals()
            ->with(['referredUser:id,name,email,created_at'])
            ->latest()
            ->paginate(10);

        // Get user's rewards
        $rewards = $user->referralRewards()
            ->with('referral.referredUser:id,name')
            ->latest()
            ->paginate(10);

        // Get expiring rewards
        $expiringRewards = $user->referralRewards()
            ->expiring(30)
            ->count();

        return Inertia::render('Referrals/Index', [
            'stats' => $stats,
            'referrals' => $referrals,
            'rewards' => $rewards,
            'expiringRewards' => $expiringRewards,
            'referralUrl' => $user->getReferralUrl(),
            'referralCode' => $user->generateReferralCode(),
        ]);
    }

    public function generateCode()
    {
        $user = Auth::user();
        $code = $user->generateReferralCode();

        return response()->json([
            'code' => $code,
            'url' => $user->getReferralUrl(),
        ]);
    }

    public function getStats()
    {
        $user = Auth::user();
        $stats = $user->getReferralStats();

        return response()->json($stats);
    }

    public function rewards()
    {
        $user = Auth::user();

        $rewards = $user->referralRewards()
            ->with('referral.referredUser:id,name')
            ->latest()
            ->paginate(15);

        $summary = [
            'total_earned' => $user->getTotalEarnedCredits(),
            'available_credits' => $user->getAvailableCredits(),
            'used_credits' => $user->referralRewards()->used()->sum('amount'),
            'expired_credits' => $user->referralRewards()->expired()->sum('amount'),
        ];

        return Inertia::render('Referrals/Rewards', [
            'rewards' => $rewards,
            'summary' => $summary,
        ]);
    }

    public function leaderboard()
    {
        // Get top referrers (public leaderboard)
        $topReferrers = User::select(['id', 'name', 'avatar', 'referral_count'])
            ->where('referral_count', '>', 0)
            ->orderByDesc('referral_count')
            ->limit(20)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'referral_count' => $user->referral_count,
                    'is_current_user' => $user->id === Auth::id(),
                ];
            });

        // Get current user's rank
        $currentUserRank = null;
        if (Auth::check()) {
            $currentUserRank = User::where('referral_count', '>', Auth::user()->referral_count)
                ->count() + 1;
        }

        return Inertia::render('Referrals/Leaderboard', [
            'topReferrers' => $topReferrers,
            'currentUserRank' => $currentUserRank,
            'currentUserStats' => Auth::check() ? Auth::user()->getReferralStats() : null,
        ]);
    }

    public function processReferral(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string|exists:users,referral_code',
            'user_id' => 'required|exists:users,id',
        ]);

        $referralCode = $request->referral_code;
        $referredUserId = $request->user_id;

        // Find the referrer
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer) {
            return response()->json(['error' => 'Code de parrainage invalide'], 400);
        }

        // Check if user is trying to refer themselves
        if ($referrer->id === $referredUserId) {
            return response()->json(['error' => 'Vous ne pouvez pas vous parrainer vous-même'], 400);
        }

        // Check if this referral already exists
        $existingReferral = Referral::where('referrer_id', $referrer->id)
            ->where('referred_user_id', $referredUserId)
            ->first();

        if ($existingReferral) {
            return response()->json(['error' => 'Ce parrainage existe déjà'], 400);
        }

        // Create the referral
        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referredUserId,
            'referral_code' => $referralCode,
            'status' => 'pending',
        ]);

        // Update the referred user's referred_by field
        User::where('id', $referredUserId)->update(['referred_by' => $referrer->id]);

        return response()->json([
            'success' => true,
            'message' => 'Parrainage créé avec succès',
            'referral' => $referral,
        ]);
    }

    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = strtoupper($request->code);
        $user = User::where('referral_code', $code)->first();

        if (!$user) {
            return response()->json([
                'valid' => false,
                'message' => 'Code de parrainage invalide',
            ]);
        }

        // Check if user is trying to use their own code
        if (Auth::check() && $user->id === Auth::id()) {
            return response()->json([
                'valid' => false,
                'message' => 'Vous ne pouvez pas utiliser votre propre code',
            ]);
        }

        return response()->json([
            'valid' => true,
            'referrer' => [
                'name' => $user->name,
                'avatar' => $user->avatar,
            ],
            'message' => "Code valide ! Vous serez parrainé par {$user->name}",
        ]);
    }

    public function share(Request $request)
    {
        $user = Auth::user();
        $referralUrl = $user->getReferralUrl();

        $shareData = [
            'url' => $referralUrl,
            'text' => "Rejoignez CarLocation avec mon code de parrainage et recevez 10€ de crédit ! Code: {$user->generateReferralCode()}",
            'hashtags' => ['CarLocation', 'Parrainage', 'LocationVoiture'],
        ];

        return response()->json($shareData);
    }

    public function markConversion(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'conversion_type' => 'required|in:registration,first_rental,first_listing',
        ]);

        $userId = $request->user_id;
        $conversionType = $request->conversion_type;

        // Find pending referral for this user
        $referral = Referral::where('referred_user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($referral) {
            $referral->markAsCompleted($conversionType);

            return response()->json([
                'success' => true,
                'message' => 'Conversion de parrainage enregistrée',
                'reward_amount' => $referral->reward_amount,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun parrainage en attente trouvé',
        ]);
    }

    public function expireRewards()
    {
        $expiredCount = ReferralReward::expireOldRewards();

        return response()->json([
            'success' => true,
            'expired_count' => $expiredCount,
            'message' => "{$expiredCount} récompenses expirées ont été traitées",
        ]);
    }

    // Admin methods
    public function adminIndex()
    {
        Gate::authorize('viewAny', Referral::class);

        $referrals = Referral::with(['referrer:id,name,email', 'referredUser:id,name,email'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total_referrals' => Referral::count(),
            'completed_referrals' => Referral::completed()->count(),
            'pending_referrals' => Referral::pending()->count(),
            'total_rewards_paid' => Referral::where('reward_paid', true)->sum('reward_amount'),
        ];

        return Inertia::render('Admin/Referrals/Index', [
            'referrals' => $referrals,
            'stats' => $stats,
        ]);
    }

    public function adminStats()
    {
        Gate::authorize('viewAny', Referral::class);

        $stats = [
            'referrals_by_month' => Referral::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month', 'year')
                ->orderBy('month')
                ->get(),
            'top_referrers' => User::select(['id', 'name', 'email', 'referral_count'])
                ->where('referral_count', '>', 0)
                ->orderByDesc('referral_count')
                ->limit(10)
                ->get(),
            'conversion_rates' => [
                'registration' => Referral::where('conversion_type', 'registration')->count(),
                'first_rental' => Referral::where('conversion_type', 'first_rental')->count(),
                'first_listing' => Referral::where('conversion_type', 'first_listing')->count(),
            ],
        ];

        return response()->json($stats);
    }
}
