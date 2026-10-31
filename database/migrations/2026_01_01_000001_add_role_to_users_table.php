<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Staff role: 'admin' or 'guard'. This table holds Admin/Guard
            // accounts only — visitors have their own separate `visitors`
            // table (see create_visitors_table) since they authenticate
            // independently and have a very different set of fields.
            $table->enum('role', ['admin', 'guard'])->default('guard')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
