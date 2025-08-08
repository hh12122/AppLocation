<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('driving_license_front')->nullable()->after('driving_license_expiry');
            $table->string('driving_license_back')->nullable()->after('driving_license_front');
            $table->enum('driving_license_status', ['pending', 'verified', 'rejected'])->default('pending')->after('driving_license_back');
            $table->timestamp('driving_license_verified_at')->nullable()->after('driving_license_status');
            $table->text('driving_license_rejection_reason')->nullable()->after('driving_license_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'driving_license_front',
                'driving_license_back',
                'driving_license_status',
                'driving_license_verified_at',
                'driving_license_rejection_reason'
            ]);
        });
    }
};