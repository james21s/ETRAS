<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pivot for the many-to-many "multi-disciplinary indexing" (papers <-> sectors). */
    public function up(): void
    {
        Schema::create('paper_sector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['paper_id', 'sector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_sector');
    }
};
