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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique();
            $table->string('email')->nullable()->change();
            $table->string('role', 50)->default('petugas_loket');
            $table->foreignId('loket_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => "user-{$user->id}"]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['loket_id']);
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'loket_id', 'is_active', 'last_login_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
