<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add polymorphic columns
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('conversable_type')->nullable()->after('id');
            $table->unsignedBigInteger('conversable_id')->nullable()->after('conversable_type');
        });

        // 2. Backfill existing rental conversations
        DB::statement("UPDATE conversations SET conversable_type = 'App\\Models\\Rental', conversable_id = rental_id WHERE rental_id IS NOT NULL");

        // 3. Drop old unique on rental_id, add new unique on morph pair
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['rental_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(['conversable_type', 'conversable_id']);
            $table->index(['conversable_type']);
        });

        // 4. Drop rental_id column
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['rental_id']);
            $table->dropColumn('rental_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('rental_id')->nullable()->constrained()->onDelete('cascade');
        });

        DB::statement("UPDATE conversations SET rental_id = conversable_id WHERE conversable_type = 'App\\Models\\Rental'");

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['conversable_type', 'conversable_id']);
            $table->dropIndex(['conversable_type']);
            $table->dropColumn(['conversable_type', 'conversable_id']);
            $table->unique(['rental_id']);
        });
    }
};
