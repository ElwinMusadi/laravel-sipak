<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('baps', function (Blueprint $table): void {
            $table->string('document_number', 50)->nullable()->after('id');
        });

        DB::table('baps')
            ->join('lokets', 'lokets.id', '=', 'baps.loket_id')
            ->orderBy('baps.id')
            ->select(['baps.id', 'baps.created_at', 'lokets.code', 'lokets.name'])
            ->each(function (object $bap): void {
                $createdAt = CarbonImmutable::parse($bap->created_at);
                $loketCode = match ($bap->code) {
                    'MPP' => 'MPP',
                    'SAMSAT-KANTOR' => 'LOKET',
                    default => Str::upper((string) preg_replace('/[^\\pL\\pN]/u', '', $bap->name)),
                };

                DB::table('baps')
                    ->where('id', $bap->id)
                    ->update([
                        'document_number' => sprintf('PB/%s/%s', $loketCode, $createdAt->format('d/m/Y')),
                    ]);
            });

        Schema::table('baps', function (Blueprint $table): void {
            $table->string('document_number', 50)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baps', function (Blueprint $table): void {
            $table->dropColumn('document_number');
        });
    }
};
