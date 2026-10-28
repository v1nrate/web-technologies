<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('builds', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('items');
            $table->unsignedInteger('total_price');
            $table->json('warnings');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('builds'); }
};
