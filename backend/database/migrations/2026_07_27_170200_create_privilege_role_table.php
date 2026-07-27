<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privilege_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('privilege_id')->constrained()->cascadeOnDelete();

            $table->unique(['role_id', 'privilege_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privilege_role');
    }
};
