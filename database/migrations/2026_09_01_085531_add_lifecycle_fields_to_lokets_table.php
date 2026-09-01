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
        Schema::table('lokets', function (Blueprint $table): void {
            $table->string('code', 50)->nullable()->after('id');
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('description');
        });

        DB::table('lokets')
            ->orderBy('id')
            ->each(function (object $loket): void {
                DB::table('lokets')
                    ->where('id', $loket->id)
                    ->update(['code' => 'LOKET-'.str_pad((string) $loket->id, 4, '0', STR_PAD_LEFT)]);
            });

        Schema::table('lokets', function (Blueprint $table): void {
            $table->string('code', 50)->nullable(false)->change();
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lokets', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'description', 'is_active']);
        });
    }
};
