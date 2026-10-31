<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facial_analysis_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facial_analysis_id')->constrained('facial_analysis')->cascadeOnDelete();
            // A match is against either another visitor OR a blacklist entry.
            $table->foreignId('matched_visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete();
            $table->foreignId('matched_blacklist_id')->nullable()->constrained('blacklist')->cascadeOnDelete();
            $table->double('similarity'); // cosine similarity
            $table->timestamp('created_at')->useCurrent();
        });

        // Carry over anything already recorded in the old `matches` table.
        if (Schema::hasTable('matches')) {
            DB::statement(
                'INSERT INTO facial_analysis_matches (id, facial_analysis_id, matched_visitor_id, similarity, created_at)
                 SELECT id, facial_analysis_id, matched_visitor_id, similarity, created_at FROM matches'
            );
            Schema::drop('matches');
        }
    }

    public function down(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facial_analysis_id')->constrained('facial_analysis')->cascadeOnDelete();
            $table->foreignId('matched_visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->double('similarity');
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement(
            'INSERT INTO matches (id, facial_analysis_id, matched_visitor_id, similarity, created_at)
             SELECT id, facial_analysis_id, matched_visitor_id, similarity, created_at
             FROM facial_analysis_matches WHERE matched_visitor_id IS NOT NULL'
        );

        Schema::dropIfExists('facial_analysis_matches');
    }
};
