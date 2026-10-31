<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blacklist', function (Blueprint $table) {
            // Cached 512-dim face vector of the reference photo, so each new
            // registration doesn't have to re-run the AI model on it.
            $table->longText('embedding')->nullable()->after('reference_photo');
        });
    }

    public function down(): void
    {
        Schema::table('blacklist', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });
    }
};
