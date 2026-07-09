<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // schema.org BlogPosting is the precise subtype for blog content —
        // Article is kept in the enum for other future use, but blog_post
        // switches over. Rename in place (not delete+recreate) so
        // is_active/label/sort_order survive; JsonldService::syncForModel()
        // overwrites the stored payload on next sync.
        DB::table('jsonld_templates')
            ->where('schema_type', 'Article')
            ->update(['schema_type' => 'BlogPosting']);

        DB::table('jsonld_schemas')
            ->where('model_type', 'blog_post')
            ->where('schema_type', 'Article')
            ->update(['schema_type' => 'BlogPosting']);
    }

    public function down(): void
    {
        DB::table('jsonld_templates')
            ->where('schema_type', 'BlogPosting')
            ->update(['schema_type' => 'Article']);

        DB::table('jsonld_schemas')
            ->where('model_type', 'blog_post')
            ->where('schema_type', 'BlogPosting')
            ->update(['schema_type' => 'Article']);
    }
};
