<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LicenseVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_their_own_license_page(): void
    {
        $user = User::factory()->create([
            'driving_license_status' => 'pending',
            'driving_license_number' => 'ABC123',
        ]);

        $response = $this->actingAs($user)->get(route('license.verification'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/LicenseVerification')
            ->where('user.id', $user->id)
            ->where('user.driving_license_number', 'ABC123')
        );
    }

    public function test_guest_is_redirected_from_license_page(): void
    {
        $this->get(route('license.verification'))->assertRedirect(route('login'));
    }

    public function test_user_can_upload_license(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('license.upload'), [
            'driving_license_number' => 'DL-2024-ABC',
            'driving_license_expiry' => now()->addYears(2)->format('Y-m-d'),
            'driving_license_front' => UploadedFile::fake()->image('front.jpg'),
            'driving_license_back' => UploadedFile::fake()->image('back.jpg'),
        ]);

        $response->assertRedirect(route('license.verification'));

        $user->refresh();
        $this->assertEquals('pending', $user->driving_license_status);
        $this->assertEquals('DL-2024-ABC', $user->driving_license_number);
        $this->assertNotNull($user->driving_license_front);
        $this->assertNotNull($user->driving_license_back);
        $this->assertNull($user->driving_license_verified_at);
        $this->assertNull($user->driving_license_rejection_reason);
    }

    public function test_upload_requires_all_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('license.upload'), []);

        $response->assertSessionHasErrors([
            'driving_license_number',
            'driving_license_expiry',
            'driving_license_front',
            'driving_license_back',
        ]);
    }

    public function test_upload_rejects_expired_date(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('license.upload'), [
            'driving_license_number' => 'DL-2024-ABC',
            'driving_license_expiry' => now()->subDay()->format('Y-m-d'),
            'driving_license_front' => UploadedFile::fake()->image('front.jpg'),
            'driving_license_back' => UploadedFile::fake()->image('back.jpg'),
        ]);

        $response->assertSessionHasErrors(['driving_license_expiry']);
    }

    public function test_admin_can_verify_license(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create(['driving_license_status' => 'pending']);

        $response = $this->actingAs($admin)
            ->post(route('admin.license-verifications.verify', $targetUser), [
                'status' => 'verified',
            ]);

        $response->assertRedirect(route('admin.license-verifications'));

        $targetUser->refresh();
        $this->assertEquals('verified', $targetUser->driving_license_status);
        $this->assertTrue((bool) $targetUser->is_verified);
        $this->assertNotNull($targetUser->driving_license_verified_at);
    }

    public function test_admin_can_reject_license_with_reason(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create(['driving_license_status' => 'pending']);

        $response = $this->actingAs($admin)
            ->post(route('admin.license-verifications.verify', $targetUser), [
                'status' => 'rejected',
                'rejection_reason' => 'Photo illisible.',
            ]);

        $response->assertRedirect(route('admin.license-verifications'));

        $targetUser->refresh();
        $this->assertEquals('rejected', $targetUser->driving_license_status);
        $this->assertEquals('Photo illisible.', $targetUser->driving_license_rejection_reason);
        $this->assertNull($targetUser->driving_license_verified_at);
    }

    public function test_non_admin_cannot_verify_license(): void
    {
        $regularUser = User::factory()->create();
        $targetUser = User::factory()->create(['driving_license_status' => 'pending']);

        $response = $this->actingAs($regularUser)
            ->post(route('admin.license-verifications.verify', $targetUser), [
                'status' => 'verified',
            ]);

        $response->assertForbidden();
    }
}
