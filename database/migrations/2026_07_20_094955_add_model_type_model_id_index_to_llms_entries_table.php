<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same gap as sitemap_entries (see the sibling migration added alongside
     * this one): only a unique index led by llms_document_id exists, nothing
     * serves a lookup by model_type+model_id alone — exactly what
     * BlogPost::llmsEntries() (used by blog-post:activate-scheduled) queries.
     * CLAUDE.md requires a composite index on every polymorphic pair.
     */
    public function up(): void
    {
        Schema::table('llms_entries', function (Blueprint $table) {
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::table('llms_entries', function (Blueprint $table) {
            $table->dropIndex(['model_type', 'model_id']);
        });
    }
};
