<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Derived state: whether the public site may show this page right now. It
     * lets the public queries filter on one indexed boolean instead of
     * evaluating a publish date on every request, and gives the scheduler a
     * column to flip when a scheduled date finally arrives.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_live')->default(false)->after('published_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['is_live']);
            $table->dropColumn('is_live');
        });
    }
};
