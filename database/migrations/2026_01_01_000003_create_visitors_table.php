<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password'); // bcrypt hash, via Hash::make()
            $table->string('selfie_path')->nullable();
            $table->string('id_card_path')->nullable();
            $table->string('purpose_of_visit')->nullable();
            $table->enum('status', ['pending', 'flagged', 'approved', 'denied', 'blacklisted'])
                ->default('pending');
            $table->string('status_reason')->nullable();
            $table->enum('registration_type', ['online', 'walk_in'])->default('online');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
