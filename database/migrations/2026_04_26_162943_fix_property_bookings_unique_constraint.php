<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('property_bookings')) {
            $indexes = collect(Schema::getIndexes('property_bookings'))->pluck('name');
            if ($indexes->contains('unique_property_date_guest')) {
                Schema::table('property_bookings', function (Blueprint $table) {
                    $table->dropUnique('unique_property_date_guest');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('property_bookings')) {
            $indexes = collect(Schema::getIndexes('property_bookings'))->pluck('name');
            if (! $indexes->contains('unique_property_date_guest')) {
                Schema::table('property_bookings', function (Blueprint $table) {
                    $table->unique(['property_id', 'checkin_date', 'guest_id'], 'unique_property_date_guest');
                });
            }
        }
    }
};
