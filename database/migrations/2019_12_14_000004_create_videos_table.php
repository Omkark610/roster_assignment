<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/create_videos_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->onDelete('cascade')->nullable()->change();
            $table->string('title')->nullable();
            $table->string('url');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('videos');
    }
};