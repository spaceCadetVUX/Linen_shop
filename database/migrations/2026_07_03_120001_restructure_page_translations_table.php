<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // page_translations was empty from the start — safe to restructure without a data migration.
        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropUnique(['locale', 'page_key']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['page_key', 'is_active']);
        });

        Schema::table('page_translations', function (Blueprint $table) {
            $table->foreignId('page_id')
                ->after('id')
                ->constrained('pages')
                ->cascadeOnDelete();

            $table->unique(['page_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropUnique(['page_id', 'locale']);
            $table->dropConstrainedForeignId('page_id');
        });

        Schema::table('page_translations', function (Blueprint $table) {
            $table->string('page_key', 100)->comment('about | contact | faq | terms');
            $table->boolean('is_active')->default(true);

            $table->unique(['locale', 'page_key']);
            $table->index('is_active');
        });
    }
};
