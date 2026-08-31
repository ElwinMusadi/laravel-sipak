<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SkpdAllocationController;
use App\Http\Controllers\SkpdBapCancellationController;
use App\Http\Controllers\SkpdBapController;
use App\Http\Controllers\SkpdBoxController;
use App\Http\Controllers\SkpdInventoryController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('can:view-bap-cancellations')
        ->prefix('bap-cancellations')
        ->name('bap-cancellations.')
        ->group(function (): void {
            Route::get('/', [SkpdBapCancellationController::class, 'index'])->name('index');
            Route::get('{bapCancellation}', [SkpdBapCancellationController::class, 'show'])->name('show');
        });

    Route::middleware('can:view-baps')
        ->prefix('baps')
        ->name('baps.')
        ->group(function (): void {
            Route::get('/', [SkpdBapController::class, 'index'])->name('index');
            Route::get('create', [SkpdBapController::class, 'create'])
                ->middleware('can:create-bap')
                ->name('create');
            Route::post('/', [SkpdBapController::class, 'store'])
                ->middleware('can:create-bap')
                ->name('store');
            Route::get('{bap}/cancellations/create', [SkpdBapCancellationController::class, 'create'])
                ->middleware('can:create-bap-cancellation,bap')
                ->name('cancellations.create');
            Route::post('{bap}/cancellations', [SkpdBapCancellationController::class, 'store'])
                ->middleware('can:create-bap-cancellation,bap')
                ->name('cancellations.store');
            Route::get('{bap}', [SkpdBapController::class, 'show'])->name('show');
            Route::get('{bap}/edit', [SkpdBapController::class, 'edit'])->name('edit');
            Route::put('{bap}', [SkpdBapController::class, 'update'])->name('update');
            Route::post('{bap}/submit', [SkpdBapController::class, 'submit'])->name('submit');
        });
});

Route::middleware(['auth', 'active', 'can:manage-users'])
    ->prefix('users')
    ->name('users.')
    ->group(function (): void {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('{user}', [UserManagementController::class, 'show'])->name('show');
        Route::get('{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('{user}', [UserManagementController::class, 'update'])->name('update');
        Route::post('{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
    });

Route::middleware(['auth', 'active', 'can:view-skpd-inventory'])
    ->prefix('skpd')
    ->name('skpd.')
    ->group(function (): void {
        Route::get('inventory', SkpdInventoryController::class)->name('inventory.index');

        Route::middleware('can:view-central-skpd-inventory')->group(function (): void {
            Route::resource('boxes', SkpdBoxController::class)
                ->only(['index', 'create', 'store', 'show']);
        });

        Route::get('allocations', [SkpdAllocationController::class, 'index'])->name('allocations.index');
        Route::get('allocations/create', [SkpdAllocationController::class, 'create'])
            ->middleware('can:manage-skpd-inventory')
            ->name('allocations.create');
        Route::post('allocations', [SkpdAllocationController::class, 'store'])
            ->middleware('can:manage-skpd-inventory')
            ->name('allocations.store');
        Route::get('allocations/{skpdAllocation}', [SkpdAllocationController::class, 'show'])->name('allocations.show');
        Route::post('allocations/{skpdAllocation}/accept', [SkpdAllocationController::class, 'accept'])->name('allocations.accept');
        Route::post('allocations/{skpdAllocation}/cancel', [SkpdAllocationController::class, 'cancel'])->name('allocations.cancel');
    });

require __DIR__.'/settings.php';
