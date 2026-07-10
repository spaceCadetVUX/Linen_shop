<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jsonld_schemas', function (Blueprint $table) {
            // '' for every schema_type except VideoObject, which stores the
            // video's own id — lets a product have N VideoObject rows per
            // locale while every other schema_type stays a singleton per
            // (model_type, model_id, schema_type, locale).
            $table->string('source_id', 36)->default('')->after('locale');
        });

        // Backfill any legacy VideoObject rows (previously deduped by a
        // `label` string built from the video title) onto their own row id
        // so the new unique index below can't collide with them.
        DB::table('jsonld_schemas')
            ->where('schema_type', 'VideoObject')
            ->orderBy('id')
            ->get(['id'])
            ->each(fn ($row) => DB::table('jsonld_schemas')
                ->where('id', $row->id)
                ->update(['source_id' => (string) $row->id]));

        Schema::table('jsonld_schemas', function (Blueprint $table) {
            // Closes the updateOrCreate() race: two 'seo' queue workers
            // (Horizon allows up to 3 concurrently in production) syncing
            // the same model+locale+schema_type at once could both pass a
            // first() check and both create() — this constraint plus
            // JsonldService::upsertJsonldSchema() makes the write atomic.
            $table->unique(
                ['model_type', 'model_id', 'schema_type', 'locale', 'source_id'],
                'jsonld_schemas_model_schema_locale_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('jsonld_schemas', function (Blueprint $table) {
            $table->dropUnique('jsonld_schemas_model_schema_locale_source_unique');
            $table->dropColumn('source_id');
        });
    }
};
