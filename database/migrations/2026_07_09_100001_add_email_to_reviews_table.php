<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Guest reviews are allowed (no storefront login exists yet) — email lets
            // admin follow up / verify the reviewer. Never shown publicly, admin-only.
            $table->string('email', 255)->nullable()->after('author');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
