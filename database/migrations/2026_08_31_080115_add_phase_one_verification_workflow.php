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
        DB::table('baps')
            ->where('status', 'waiting_verification')
            ->update(['status' => 'submitted']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'under_verification', 'needs_clarification', 'waiting_verification_phase_2'))");
        }

        Schema::table('baps', function (Blueprint $table): void {
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('bap_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_id')->constrained()->restrictOnDelete();
            $table->foreignId('verifier_id')->constrained('users')->restrictOnDelete();
            $table->string('stage', 32);
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('status', 32);
            $table->string('result', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['bap_id', 'stage', 'attempt']);
            $table->index(['stage', 'status', 'started_at']);
            $table->index(['verifier_id', 'status']);
        });

        Schema::create('bap_verification_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_verification_id')->constrained()->restrictOnDelete();
            $table->string('type', 32);
            $table->boolean('is_attested');
            $table->unsignedInteger('expected_quantity')->nullable();
            $table->unsignedInteger('actual_quantity')->nullable();
            $table->integer('quantity_difference')->nullable();
            $table->unsignedInteger('expected_numerator_start')->nullable();
            $table->unsignedInteger('expected_numerator_end')->nullable();
            $table->unsignedInteger('actual_numerator_start')->nullable();
            $table->unsignedInteger('actual_numerator_end')->nullable();
            $table->timestamps();

            $table->unique(['bap_verification_id', 'type']);
            $table->index('type');
        });

        Schema::create('bap_verification_discrepancies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_verification_id')->constrained()->restrictOnDelete();
            $table->foreignId('bap_verification_checklist_item_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('type', 32);
            $table->string('expected_value', 64);
            $table->string('actual_value', 64);
            $table->integer('difference')->nullable();
            $table->text('notes');
            $table->timestamps();

            $table->unique('bap_verification_checklist_item_id');
            $table->index(['type', 'created_at']);
        });

        Schema::create('bap_clarification_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bap_id')->constrained()->restrictOnDelete();
            $table->foreignId('bap_verification_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('bap_verification_id');
            $table->index(['bap_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bap_clarification_requests');
        Schema::dropIfExists('bap_verification_discrepancies');
        Schema::dropIfExists('bap_verification_checklist_items');
        Schema::dropIfExists('bap_verifications');

        Schema::table('baps', function (Blueprint $table): void {
            $table->dropIndex(['status', 'submitted_at']);
        });

        DB::table('baps')
            ->whereIn('status', ['under_verification', 'needs_clarification', 'waiting_verification_phase_2'])
            ->update(['status' => 'submitted']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE baps DROP CHECK baps_status_check');
            DB::statement("ALTER TABLE baps ADD CONSTRAINT baps_status_check CHECK (status IN ('draft', 'submitted', 'waiting_verification'))");
        }
    }
};
