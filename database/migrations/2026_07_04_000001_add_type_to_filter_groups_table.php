<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filter_groups', function (Blueprint $table) {
            // 'text' | 'color' — see App\Enums\FilterGroupType. Drives which
            // input the admin shows per value (ColorPicker vs none) and which
            // renderer the storefront uses (swatch vs pill).
            $table->string('type', 20)->default('text')->after('slug');
        });

        // Backfill: any group that already has swatch data is a color group.
        DB::table('filter_groups')
            ->whereIn('id', DB::table('filter_values')
                ->whereNotNull('color_hex')
                ->distinct()
                ->pluck('filter_group_id'))
            ->update(['type' => 'color']);
    }

    public function down(): void
    {
        Schema::table('filter_groups', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
