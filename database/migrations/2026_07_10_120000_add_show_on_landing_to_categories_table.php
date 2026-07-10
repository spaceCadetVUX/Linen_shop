<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Toggle in CategoryResource's General tab — controls whether this
            // category gets its own product row on the homepage "Sản phẩm nổi
            // bật" section. Display order among featured categories reuses the
            // existing `sort_order` column instead of a dedicated one.
            $table->boolean('show_on_landing')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('show_on_landing');
        });
    }
};
