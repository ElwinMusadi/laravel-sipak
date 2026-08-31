<?php

namespace App\Http\Controllers;

use App\BapStatus;
use App\BapVerificationResult;
use App\BapVerificationStage;
use App\BapVerificationStatus;
use App\Models\Bap;
use App\Models\BapVerification;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display BAP-derived work for the current user's authorized scope.
     */
    public function __invoke(Request $request): Response
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        $bapQuery = Bap::query();

        if (! $actor->can('view-all-baps')) {
            $bapQuery->where('loket_id', $actor->loket_id);
        }

        $todayCount = (clone $bapQuery)
            ->whereDate('service_date', now()->toDateString())
            ->count();

        if ($actor->role === UserRole::PetugasPenetapan) {
            return Inertia::render('dashboard', [
                'dashboard' => $this->phaseOneDashboard($actor, $bapQuery, $todayCount),
            ]);
        }

        if ($actor->role === UserRole::PetugasVerifikasi) {
            return Inertia::render('dashboard', [
                'dashboard' => $this->phaseTwoDashboard($actor, $bapQuery, $todayCount),
            ]);
        }

        if ($actor->role === UserRole::PetugasLoket) {
            return Inertia::render('dashboard', [
                'dashboard' => $this->loketDashboard($bapQuery, $todayCount),
            ]);
        }

        $waitingCount = (clone $bapQuery)
            ->whereIn('status', [BapStatus::Submitted->value, BapStatus::UnderVerification->value])
            ->count();

        return Inertia::render('dashboard', [
            'dashboard' => [
                'metrics' => [
                    [
                        'id' => 'today',
                        'label' => 'BAP Hari Ini',
                        'value' => $todayCount,
                        'description' => 'BAP pada tanggal pelayanan hari ini',
                    ],
                    [
                        'id' => 'waiting',
                        'label' => 'Menunggu Verifikasi',
                        'value' => $waitingCount,
                        'description' => 'BAP yang telah diajukan',
                    ],
                ],
                'workItems' => [
                    [
                        'id' => 'waiting',
                        'title' => 'BAP menunggu verifikasi',
                        'count' => $waitingCount,
                        'status' => 'waiting',
                        'description' => $actor->can('view-all-baps')
                            ? 'BAP yang siap masuk workflow verifikasi berikutnya.'
                            : 'BAP Loket Anda yang telah diajukan.',
                        'href' => route('baps.index'),
                    ],
                ],
                'recentBaps' => (clone $bapQuery)
                    ->with(['loket:id,name'])
                    ->orderByDesc('service_date')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Bap $bap): array => [
                        'id' => $bap->id,
                        'loket' => $bap->loket->name,
                        'serviceDate' => $bap->service_date->toDateString(),
                        'submittedAt' => $bap->submitted_at?->toIso8601String(),
                        'status' => $this->dashboardStatus($bap->status),
                    ])
                    ->all(),
            ],
        ]);
    }

    /**
     * @param  Builder<Bap>  $bapQuery
     * @return array<string, mixed>
     */
    private function phaseOneDashboard(User $actor, Builder $bapQuery, int $todayCount): array
    {
        $verificationQuery = BapVerification::query()
            ->where('stage', BapVerificationStage::Phase1->value)
            ->where('verifier_id', $actor->id);
        $waitingCount = (clone $bapQuery)->where('status', BapStatus::Submitted->value)->count();
        $inProgressCount = (clone $verificationQuery)
            ->where('status', BapVerificationStatus::InProgress->value)
            ->count();
        $discrepancyCount = (clone $verificationQuery)
            ->where('result', BapVerificationResult::Discrepancy->value)
            ->count();
        $completedCount = (clone $verificationQuery)
            ->where('result', BapVerificationResult::Passed->value)
            ->count();

        return [
            'metrics' => [
                [
                    'id' => 'today',
                    'label' => 'BAP Hari Ini',
                    'value' => $todayCount,
                    'description' => 'BAP pada tanggal pelayanan hari ini',
                ],
                [
                    'id' => 'waiting',
                    'label' => 'Menunggu Verifikasi Tahap 1',
                    'value' => $waitingCount,
                    'description' => 'BAP submitted yang siap diperiksa',
                ],
                [
                    'id' => 'in_progress',
                    'label' => 'Sedang Diverifikasi',
                    'value' => $inProgressCount,
                    'description' => 'Pemeriksaan fisik yang Anda mulai',
                ],
                [
                    'id' => 'discrepancy',
                    'label' => 'Ada Selisih',
                    'value' => $discrepancyCount,
                    'description' => 'Hasil Anda yang menunggu klarifikasi',
                ],
            ],
            'workItems' => [
                [
                    'id' => 'waiting-phase-1',
                    'title' => 'BAP menunggu Verifikasi Tahap 1',
                    'count' => $waitingCount,
                    'status' => 'waiting',
                    'description' => 'Mulai pemeriksaan dari antrean verifikasi.',
                    'href' => route('bap-verifications.index'),
                ],
                [
                    'id' => 'in-progress-phase-1',
                    'title' => 'Pemeriksaan fisik berlangsung',
                    'count' => $inProgressCount,
                    'status' => 'in_progress',
                    'description' => 'Selesaikan checklist yang Anda mulai.',
                    'href' => route('bap-verifications.index'),
                ],
                [
                    'id' => 'discrepancy-phase-1',
                    'title' => 'Selisih yang telah dicatat',
                    'count' => $discrepancyCount,
                    'status' => 'discrepancy',
                    'description' => 'Menunggu proses klarifikasi pada fase berikutnya.',
                    'href' => route('baps.index', ['status' => BapStatus::NeedsClarification->value]),
                ],
                [
                    'id' => 'completed-phase-1',
                    'title' => 'Lulus Verifikasi Tahap 1',
                    'count' => $completedCount,
                    'status' => 'completed',
                    'description' => 'BAP telah diteruskan ke antrean Tahap 2.',
                    'href' => route('baps.index', ['status' => BapStatus::WaitingVerificationPhase2->value]),
                ],
            ],
            'recentBaps' => $this->recentBaps($bapQuery),
        ];
    }

    /**
     * @param  Builder<Bap>  $bapQuery
     * @return array<string, mixed>
     */
    private function phaseTwoDashboard(User $actor, Builder $bapQuery, int $todayCount): array
    {
        $verificationQuery = BapVerification::query()
            ->where('stage', BapVerificationStage::Phase2->value)
            ->where('verifier_id', $actor->id);
        $waitingCount = (clone $bapQuery)->where('status', BapStatus::WaitingVerificationPhase2->value)->count();
        $inProgressCount = (clone $verificationQuery)
            ->where('status', BapVerificationStatus::InProgress->value)
            ->count();
        $discrepancyCount = (clone $verificationQuery)
            ->where('result', BapVerificationResult::Discrepancy->value)
            ->count();
        $completedCount = (clone $verificationQuery)
            ->where('result', BapVerificationResult::Passed->value)
            ->count();

        return [
            'metrics' => [
                [
                    'id' => 'today',
                    'label' => 'BAP Hari Ini',
                    'value' => $todayCount,
                    'description' => 'BAP pada tanggal pelayanan hari ini',
                ],
                [
                    'id' => 'waiting',
                    'label' => 'Menunggu Verifikasi Tahap 2',
                    'value' => $waitingCount,
                    'description' => 'BAP yang lulus Verifikasi Tahap 1',
                ],
                [
                    'id' => 'in_progress',
                    'label' => 'Sedang Diverifikasi Tahap 2',
                    'value' => $inProgressCount,
                    'description' => 'Pemeriksaan fisik yang Anda mulai',
                ],
                [
                    'id' => 'discrepancy',
                    'label' => 'Ada Selisih',
                    'value' => $discrepancyCount,
                    'description' => 'Hasil Anda yang menunggu klarifikasi',
                ],
            ],
            'workItems' => [
                [
                    'id' => 'waiting-phase-2',
                    'title' => 'BAP menunggu Verifikasi Tahap 2',
                    'count' => $waitingCount,
                    'status' => 'waiting',
                    'description' => 'Buka hasil Tahap 1 sebelum memulai pemeriksaan fisik.',
                    'href' => route('bap-verifications-phase-2.index'),
                ],
                [
                    'id' => 'in-progress-phase-2',
                    'title' => 'Pemeriksaan fisik berlangsung',
                    'count' => $inProgressCount,
                    'status' => 'in_progress',
                    'description' => 'Selesaikan checklist yang Anda mulai.',
                    'href' => route('bap-verifications-phase-2.index'),
                ],
                [
                    'id' => 'discrepancy-phase-2',
                    'title' => 'Selisih yang telah dicatat',
                    'count' => $discrepancyCount,
                    'status' => 'discrepancy',
                    'description' => 'Menunggu proses klarifikasi pada fase berikutnya.',
                    'href' => route('baps.index', ['status' => BapStatus::NeedsClarification->value]),
                ],
                [
                    'id' => 'completed-phase-2',
                    'title' => 'Lulus Verifikasi Tahap 2',
                    'count' => $completedCount,
                    'status' => 'completed',
                    'description' => 'BAP siap menjadi input proses Bendahara Barang berikutnya.',
                    'href' => route('baps.index', ['status' => BapStatus::VerifiedPhase2->value]),
                ],
            ],
            'recentBaps' => $this->recentBaps($bapQuery),
        ];
    }

    /**
     * @param  Builder<Bap>  $bapQuery
     * @return array<string, mixed>
     */
    private function loketDashboard(Builder $bapQuery, int $todayCount): array
    {
        $waitingCount = (clone $bapQuery)
            ->whereIn('status', [BapStatus::Submitted->value, BapStatus::UnderVerification->value])
            ->count();
        $clarificationCount = (clone $bapQuery)
            ->where('status', BapStatus::NeedsClarification->value)
            ->count();

        return [
            'metrics' => [
                [
                    'id' => 'today',
                    'label' => 'BAP Hari Ini',
                    'value' => $todayCount,
                    'description' => 'BAP pada tanggal pelayanan hari ini',
                ],
                [
                    'id' => 'waiting',
                    'label' => 'BAP Menunggu Verifikasi',
                    'value' => $waitingCount,
                    'description' => 'BAP Anda yang sedang pada Verifikasi Tahap 1',
                ],
                [
                    'id' => 'discrepancy',
                    'label' => 'Perlu Tindak Lanjut',
                    'value' => $clarificationCount,
                    'description' => 'BAP yang memerlukan klarifikasi',
                ],
            ],
            'workItems' => [
                [
                    'id' => 'waiting',
                    'title' => 'BAP menunggu verifikasi',
                    'count' => $waitingCount,
                    'status' => 'waiting',
                    'description' => 'BAP telah diajukan dan bersifat read-only.',
                    'href' => route('baps.index'),
                ],
                [
                    'id' => 'clarification',
                    'title' => 'BAP perlu tindak lanjut',
                    'count' => $clarificationCount,
                    'status' => 'discrepancy',
                    'description' => 'Detail selisih tersedia, alur klarifikasi lengkap belum dibangun.',
                    'href' => route('baps.index', ['status' => BapStatus::NeedsClarification->value]),
                ],
            ],
            'recentBaps' => $this->recentBaps($bapQuery),
        ];
    }

    /**
     * @param  Builder<Bap>  $bapQuery
     * @return list<array{id: int, loket: string, serviceDate: string, submittedAt: string|null, status: string}>
     */
    private function recentBaps(Builder $bapQuery): array
    {
        return array_values((clone $bapQuery)
            ->with(['loket:id,name'])
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Bap $bap): array => [
                'id' => $bap->id,
                'loket' => $bap->loket->name,
                'serviceDate' => $bap->service_date->toDateString(),
                'submittedAt' => $bap->submitted_at?->toIso8601String(),
                'status' => $this->dashboardStatus($bap->status),
            ])
            ->values()
            ->all());
    }

    private function dashboardStatus(BapStatus $status): string
    {
        return match ($status) {
            BapStatus::Draft => 'draft',
            BapStatus::Submitted => 'waiting',
            BapStatus::UnderVerification => 'in_progress',
            BapStatus::NeedsClarification => 'discrepancy',
            BapStatus::WaitingVerificationPhase2 => 'completed',
            BapStatus::UnderVerificationPhase2 => 'in_progress',
            BapStatus::VerifiedPhase2 => 'completed',
        };
    }
}
