<?php

namespace App\Http\Controllers;

use App\BapStatus;
use App\Models\Bap;
use App\Models\User;
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

        $waitingStatuses = [
            BapStatus::Submitted->value,
            BapStatus::WaitingVerification->value,
        ];
        $todayCount = (clone $bapQuery)
            ->whereDate('service_date', now()->toDateString())
            ->count();
        $waitingCount = (clone $bapQuery)
            ->whereIn('status', $waitingStatuses)
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
                        'status' => $bap->status === BapStatus::Draft ? 'draft' : 'waiting',
                    ])
                    ->all(),
            ],
        ]);
    }
}
