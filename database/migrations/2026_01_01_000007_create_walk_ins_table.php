<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('walk_ins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('id_card_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('purpose_of_visit')->nullable();
            $table->foreignId('gate_id')->nullable()->constrained('gates')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->string('status_reason')->nullable();
            $table->unsignedBigInteger('encoded_by')->nullable(); // guard user id
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('encoded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walk_ins');
    }
};
