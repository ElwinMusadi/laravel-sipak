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
        Schema::create('skpd_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('skpd_box_id')->constrained()->restrictOnDelete();
            $table->foreignId('loket_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('numerator_start');
            $table->unsignedInteger('numerator_end');
            $table->unsignedInteger('quantity');
            $table->string('status', 32)->default('pending');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['skpd_box_id', 'status']);
            $table->index(['loket_id', 'status']);
            $table->index(['skpd_box_id', 'numerator_start', 'numerator_end']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE skpd_allocations ADD CONSTRAINT skpd_allocations_range_check CHECK (numerator_start <= numerator_end)');
            DB::statement('ALTER TABLE skpd_allocations ADD CONSTRAINT skpd_allocations_quantity_check CHECK (quantity = numerator_end - numerator_start + 1)');
            DB::statement("ALTER TABLE skpd_allocations ADD CONSTRAINT skpd_allocations_status_check CHECK (status IN ('pending', 'accepted', 'completed', 'cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skpd_allocations');
    }
};
