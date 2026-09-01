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
        Schema::table('bap_clarification_requests', function (Blueprint $table): void {
            $table->foreignId('opened_by')
                ->nullable()
                ->after('requested_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('opened_at')->nullable()->after('opened_by');
        });

        DB::table('bap_clarification_requests')
            ->where('status', 'open')
            ->update(['status' => 'waiting_response']);

        Schema::create('bap_clarification_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_clarification_request_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('round');
            $table->foreignId('responded_by')->constrained('users')->restrictOnDelete();
            $table->text('response');
            $table->timestamp('responded_at');
            $table->timestamps();

            $table->unique(
                ['bap_clarification_request_id', 'round'],
                'bap_clarification_responses_request_round_unique',
            );
            $table->index(
                ['bap_clarification_request_id', 'responded_at'],
                'bap_clarification_responses_request_responded_index',
            );
        });

        Schema::create('bap_clarification_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_clarification_request_id')
                ->constrained(indexName: 'bap_clarification_resolutions_request_foreign')
                ->restrictOnDelete();
            $table->foreignId('bap_clarification_response_id')
                ->constrained(indexName: 'bap_clarification_resolutions_response_foreign')
                ->restrictOnDelete();
            $table->foreignId('resolved_by')->constrained('users')->restrictOnDelete();
            $table->string('outcome', 32);
            $table->text('notes');
            $table->timestamp('resolved_at');
            $table->timestamps();

            $table->unique(
                'bap_clarification_response_id',
                'bap_clarification_resolutions_response_unique',
            );
            $table->index(
                ['bap_clarification_request_id', 'outcome'],
                'bap_clarification_resolutions_request_outcome_index',
            );
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'under_verification', 'needs_clarification', 'waiting_reverification_phase_1', 'waiting_verification_phase_2', 'under_verification_phase_2', 'waiting_reverification_phase_2', 'verified_phase_2'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('baps')
            ->whereIn('status', ['waiting_reverification_phase_1', 'waiting_reverification_phase_2'])
            ->update(['status' => 'needs_clarification']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'under_verification', 'needs_clarification', 'waiting_verification_phase_2', 'under_verification_phase_2', 'verified_phase_2'))");
        }

        Schema::dropIfExists('bap_clarification_resolutions');
        Schema::dropIfExists('bap_clarification_responses');

        Schema::table('bap_clarification_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('opened_by');
            $table->dropColumn('opened_at');
        });
    }
};
