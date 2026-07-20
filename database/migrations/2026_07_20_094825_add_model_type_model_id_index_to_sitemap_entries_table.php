<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CLAUDE.md's polymorphic-table rule requires a composite index on every
     * (model_type, model_id) pair. sitemap_entries only had a unique index led
     * by sitemap_index_id, which doesn't serve a lookup by model_type+model_id
     * alone — exactly what BlogPost::sitemapEntries() (used by the
     * blog-post:activate-scheduled command) queries on every run.
     */
    public function up(): void
    {
        Schema::table('sitemap_entries', function (Blueprint $table) {
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sitemap_entries', function (Blueprint $table) {
            $table->dropIndex(['model_type', 'model_id']);
        });
    }
};
