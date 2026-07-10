<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // SQLite refuses to drop a column still carrying a unique index
            // (Postgres cascades this automatically, which is why the bug
            // went unnoticed) — drop the constraint explicitly first.
            $table->dropUnique(['slug']);
            $table->dropColumn(['title', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title', 255)->default('')->after('blog_category_id');
            $table->string('slug', 255)->nullable()->unique()->after('title');
        });
    }
};
