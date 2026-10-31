<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_gate_traffic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete();
            $table->foreignId('walk_in_id')->nullable()->constrained('walk_ins')->cascadeOnDelete();
            $table->foreignId('gate_id')->constrained('gates')->cascadeOnDelete();
            $table->timestamp('time_in')->nullable();
            $table->timestamp('time_out')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_gate_traffic');
    }
};
