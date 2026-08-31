<?php

namespace App\Providers;

use App\BapStatus;
use App\Models\Bap;
use App\Models\BapCancellation;
use App\Models\SkpdAllocation;
use App\Models\User;
use App\SkpdAllocationStatus;
use App\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure server-side authorization for Phase 03 administration.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->role === UserRole::Superadmin);

        Gate::define('view-skpd-inventory', fn (User $user): bool => in_array($user->role, [
            UserRole::Superadmin,
            UserRole::BendaharaBarang,
            UserRole::PetugasLoket,
        ], true));

        Gate::define('view-central-skpd-inventory', fn (User $user): bool => in_array($user->role, [
            UserRole::Superadmin,
            UserRole::BendaharaBarang,
        ], true));

        Gate::define('manage-skpd-inventory', fn (User $user): bool => $user->role === UserRole::BendaharaBarang);

        Gate::define('view-skpd-allocation', fn (User $user, SkpdAllocation $allocation): bool => $user->role === UserRole::Superadmin
            || $user->role === UserRole::BendaharaBarang
            || ($user->role === UserRole::PetugasLoket && $user->loket_id === $allocation->loket_id));

        Gate::define('accept-skpd-allocation', fn (User $user, SkpdAllocation $allocation): bool => $user->role === UserRole::PetugasLoket
            && $user->loket_id === $allocation->loket_id
            && $allocation->status === SkpdAllocationStatus::Pending);

        Gate::define('cancel-skpd-allocation', fn (User $user, SkpdAllocation $allocation): bool => $user->role === UserRole::BendaharaBarang
            && $user->id === $allocation->created_by
            && $allocation->status === SkpdAllocationStatus::Pending);

        Gate::define('view-baps', fn (User $user): bool => $user->role === UserRole::PetugasLoket
            || $this->canViewAllBaps($user));

        Gate::define('view-all-baps', fn (User $user): bool => $this->canViewAllBaps($user));

        Gate::define('view-bap', fn (User $user, Bap $bap): bool => $this->canViewBap($user, $bap));

        Gate::define('create-bap', fn (User $user): bool => $user->role === UserRole::PetugasLoket
            && $user->loket_id !== null);

        Gate::define('update-bap', fn (User $user, Bap $bap): bool => $user->role === UserRole::PetugasLoket
            && $user->loket_id === $bap->loket_id
            && $user->id === $bap->created_by
            && $bap->status === BapStatus::Draft);

        Gate::define('submit-bap', fn (User $user, Bap $bap): bool => $user->role === UserRole::PetugasLoket
            && $user->loket_id === $bap->loket_id
            && $bap->status === BapStatus::Draft);

        Gate::define('view-bap-cancellations', fn (User $user): bool => $user->can('view-baps'));

        Gate::define('view-bap-cancellation', fn (User $user, BapCancellation $cancellation): bool => $this->canViewBap($user, $cancellation->bap));

        Gate::define('create-bap-cancellation', fn (User $user, Bap $bap): bool => $user->role === UserRole::PetugasLoket
            && $user->loket_id === $bap->loket_id
            && $user->id === $bap->created_by
            && $bap->status === BapStatus::Draft);

        Gate::define('view-bap-verifications-phase-1', fn (User $user): bool => $user->role === UserRole::PetugasPenetapan);

        Gate::define('start-bap-verification-phase-1', fn (User $user, Bap $bap): bool => $user->role === UserRole::PetugasPenetapan
            && $bap->status === BapStatus::Submitted);

        Gate::define('complete-bap-verification-phase-1', fn (User $user): bool => $user->role === UserRole::PetugasPenetapan);
    }

    private function canViewBap(User $user, Bap $bap): bool
    {
        return $this->canViewAllBaps($user)
            || ($user->role === UserRole::PetugasLoket && $user->loket_id === $bap->loket_id);
    }

    private function canViewAllBaps(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Superadmin,
            UserRole::BendaharaBarang,
            UserRole::PetugasPenetapan,
            UserRole::KasiePenetapan,
            UserRole::PetugasVerifikasi,
            UserRole::KasieVerifikasi,
            UserRole::KepalaUptd,
        ], true);
    }
}
