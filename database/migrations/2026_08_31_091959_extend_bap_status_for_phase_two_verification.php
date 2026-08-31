<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'under_verification', 'needs_clarification', 'waiting_verification_phase_2', 'under_verification_phase_2', 'verified_phase_2'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'under_verification', 'needs_clarification', 'waiting_verification_phase_2'))");
        }
    }
};
