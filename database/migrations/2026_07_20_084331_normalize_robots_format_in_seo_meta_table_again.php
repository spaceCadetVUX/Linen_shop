<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MCP services (Brand/Manufacturer/Product/Batch) and BrandSeeder/CategorySeeder kept
     * writing 'index, follow' (with a space) after the 2026_07_09 fix, since only the
     * blog post/category services were normalized. Filament's robots Select only defines
     * no-space option keys, so any leftover space-variant value fails validation
     * ("The selected robots is invalid") when the record is opened in the admin.
     */
    public function up(): void
    {
        DB::table('seo_meta')
            ->where('robots', 'like', '%, %')
            ->update(['robots' => DB::raw("replace(robots, ', ', ',')")]);
    }

    public function down(): void
    {
        // Intentionally irreversible — the space variant was a bug, not a valid prior state.
    }
};
