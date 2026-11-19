<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('author')->index();
            $table->foreignId('committee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('organization')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('committee_id');
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'organization']);
        });
    }
};
