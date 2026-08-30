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
        Schema::create('bap_cancellations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('numerator')->unique();
            $table->string('reason', 32);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('bap_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bap_cancellations ADD CONSTRAINT bap_cancellations_reason_check CHECK (reason IN ('cancelled', 'damaged'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bap_cancellations');
    }
};
