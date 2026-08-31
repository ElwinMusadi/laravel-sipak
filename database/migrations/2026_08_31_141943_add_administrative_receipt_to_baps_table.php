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
        Schema::table('baps', function (Blueprint $table): void {
            $table->foreignId('received_by')
                ->nullable()
                ->after('submitted_at')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('received_at')->nullable()->after('received_by');
            $table->text('receipt_notes')->nullable()->after('received_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'under_verification', 'needs_clarification', 'waiting_reverification_phase_1', 'waiting_verification_phase_2', 'under_verification_phase_2', 'waiting_reverification_phase_2', 'verified_phase_2', 'completed'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('baps')
            ->where('status', 'completed')
            ->update(['status' => 'verified_phase_2']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'under_verification', 'needs_clarification', 'waiting_reverification_phase_1', 'waiting_verification_phase_2', 'under_verification_phase_2', 'waiting_reverification_phase_2', 'verified_phase_2'))");
        }

        Schema::table('baps', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('received_by');
            $table->dropColumn(['received_at', 'receipt_notes']);
        });
    }
};
