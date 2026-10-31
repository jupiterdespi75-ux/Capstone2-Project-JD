<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete();
            $table->foreignId('walk_in_id')->nullable()->constrained('walk_ins')->cascadeOnDelete();
            $table->foreignId('gate_id')->constrained('gates')->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->enum('scan_status', ['valid', 'flagged', 'blacklisted']);
            $table->unsignedBigInteger('scanned_by')->nullable(); // guard user id
            $table->timestamp('scanned_at')->useCurrent();

            $table->foreign('scanned_by')->references('id')->on('users')->nullOnDelete();

            // Cooldown lookups filter by exactly these three columns, most
            // recent first — index them together for that query.
            $table->index(['visitor_id', 'gate_id', 'direction', 'scanned_at'], 'cooldown_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_history');
    }
};
