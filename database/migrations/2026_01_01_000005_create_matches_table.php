<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facial_analysis_id')->constrained('facial_analysis')->cascadeOnDelete();
            $table->foreignId('matched_visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->double('similarity'); // cosine similarity 0..1
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
