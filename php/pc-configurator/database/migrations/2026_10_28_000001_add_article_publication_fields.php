<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('category', 32)->default('guides');
            $table->string('status', 16)->default('published');
            $table->string('cover_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn(['category', 'status', 'cover_url']));
    }
};
