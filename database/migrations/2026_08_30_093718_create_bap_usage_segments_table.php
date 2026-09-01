<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bap_usage_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_id')->constrained()->restrictOnDelete();
            $table->foreignId('skpd_allocation_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('numerator_start');
            $table->unsignedInteger('numerator_end');
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['bap_id', 'skpd_allocation_id']);
            $table->index(
                ['skpd_allocation_id', 'numerator_start', 'numerator_end'],
                'bap_usage_segments_allocation_range_index',
            );
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE bap_usage_segments ADD CONSTRAINT bap_usage_segments_range_check CHECK (numerator_start <= numerator_end)');
            DB::statement('ALTER TABLE bap_usage_segments ADD CONSTRAINT bap_usage_segments_quantity_check CHECK (quantity = numerator_end - numerator_start + 1)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bap_usage_segments');
    }
};
