<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            $table->string('robots', 100)->nullable()->default('index,follow')->change();
        });

        DB::table('seo_meta')
            ->where('robots', 'index, follow')
            ->update(['robots' => 'index,follow']);
    }

    public function down(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            $table->string('robots', 100)->nullable()->default('index, follow')->change();
        });
    }
};
