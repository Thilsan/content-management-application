<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arabic sits alongside the English content rather than in a translations
     * table. With exactly two languages that keeps every read on one row, and
     * a page that has no Arabic simply falls back to English.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->longText('body_ar')->nullable()->after('body');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['title_ar', 'body_ar']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('title_ar');
        });
    }
};
