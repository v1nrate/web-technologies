<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30)->index();
            $table->string('brand', 80);
            $table->string('name');
            $table->unsignedInteger('price');
            $table->json('specs');
            $table->unsignedSmallInteger('power_watts')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('components'); }
};
