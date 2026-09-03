<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the old MySQL CHECK constraint that only allowed 'cancelled' and 'damaged',
     * and replaces it with one that also accepts 'network_error', 'printer_error', and
     * 'custom' introduced by the unified BAP workflow. Historical data is not modified.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE bap_cancellations DROP CONSTRAINT bap_cancellations_reason_check');
        DB::statement(
            'ALTER TABLE bap_cancellations ADD CONSTRAINT bap_cancellations_reason_check '
            ."CHECK (reason IN ('cancelled','damaged','network_error','printer_error','custom'))"
        );
    }

    /**
     * Reverse the migrations.
     *
     * Restores the original constraint. Rows with new reason values
     * will cause the constraint to reject them, but there should be none
     * if this is rolled back before any new data is written.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE bap_cancellations DROP CONSTRAINT bap_cancellations_reason_check');
        DB::statement(
            'ALTER TABLE bap_cancellations ADD CONSTRAINT bap_cancellations_reason_check '
            ."CHECK (reason IN ('cancelled','damaged'))"
        );
    }
};
