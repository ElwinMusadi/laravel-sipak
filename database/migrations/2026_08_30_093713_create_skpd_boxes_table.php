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
        Schema::create('skpd_boxes', function (Blueprint $table): void {
            $table->id();
            $table->string('box_number', 50)->unique();
            $table->unsignedInteger('numerator_start');
            $table->unsignedInteger('numerator_end');
            $table->unsignedInteger('total_sets');
            $table->string('central_storage_location', 100)->default('Bendahara Barang');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['numerator_start', 'numerator_end']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE skpd_boxes ADD CONSTRAINT skpd_boxes_range_check CHECK (numerator_start <= numerator_end)');
            DB::statement('ALTER TABLE skpd_boxes ADD CONSTRAINT skpd_boxes_quantity_check CHECK (total_sets = numerator_end - numerator_start + 1)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skpd_boxes');
    }
};
