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
        Schema::create('baps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loket_id')->constrained()->restrictOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('numerator_start');
            $table->unsignedInteger('numerator_end');
            $table->unsignedInteger('total_usage');
            $table->unsignedInteger('online_usage_count')->default(0);
            $table->string('status', 32)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['loket_id', 'service_date']);
            $table->unique('numerator_start');
            $table->unique('numerator_end');
            $table->index(['loket_id', 'numerator_end']);
            $table->index(['status', 'service_date']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps ADD CONSTRAINT baps_range_check CHECK (numerator_start <= numerator_end)');
            DB::statement('ALTER TABLE baps ADD CONSTRAINT baps_total_usage_check CHECK (total_usage = numerator_end - numerator_start + 1)');
            DB::statement('ALTER TABLE baps ADD CONSTRAINT baps_online_usage_check CHECK (online_usage_count <= total_usage)');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'waiting_verification'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baps');
    }
};
