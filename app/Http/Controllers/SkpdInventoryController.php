<?php

namespace App\Http\Controllers;

use App\Models\BapUsageSegment;
use App\Models\Loket;
use App\Models\SkpdAllocation;
use App\Models\SkpdBox;
use App\Models\User;
use App\SkpdAllocationStatus;
use App\SkpdBoxStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkpdInventoryController extends Controller
{
    /**
     * Display inventory data according to the authenticated user's role.
     */
    public function __invoke(Request $request): Response
    {
        $actor = $this->actor($request);

        return $actor->can('view-central-skpd-inventory')
            ? $this->centralDashboard()
            : $this->loketDashboard($actor);
    }

    private function centralDashboard(): Response
    {
        $totalRegistered = (int) SkpdBox::query()->sum('total_sets');
        $pendingQuantity = (int) SkpdAllocation::query()
            ->where('status', SkpdAllocationStatus::Pending)
            ->sum('quantity');
        $allocatedQuantity = (int) SkpdAllocation::query()
            ->whereIn('status', [SkpdAllocationStatus::Accepted, SkpdAllocationStatus::Completed])
            ->sum('quantity');
        $usedQuantity = (int) BapUsageSegment::query()->sum('quantity');

        $boxes = SkpdBox::query()
            ->with(['allocations.loket', 'allocations.usageSegments'])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();

        $boxData = $boxes
            ->map(fn (SkpdBox $box): array => $this->boxData($box));

        return Inertia::render('skpd/inventory', [
            'scope' => 'central',
            'metrics' => [
                'total_boxes' => $boxes->count(),
                'total_inventory' => $totalRegistered - $usedQuantity,
                'available_quantity' => $totalRegistered - $pendingQuantity - $allocatedQuantity,
                'allocated_quantity' => $allocatedQuantity,
                'used_quantity' => $usedQuantity,
                'pending_allocations' => SkpdAllocation::query()
                    ->where('status', SkpdAllocationStatus::Pending)
                    ->count(),
                'active_allocations' => SkpdAllocation::query()
                    ->where('status', SkpdAllocationStatus::Accepted)
                    ->count(),
                'nearly_depleted_boxes' => $boxData
                    ->filter(fn (array $box): bool => $box['available_quantity'] > 0
                        && $box['available_quantity'] <= max(1, (int) ceil($box['total_sets'] * 0.1)))
                    ->count(),
            ],
            'recent_boxes' => $boxData->take(5)->values()->all(),
        ]);
    }

    private function loketDashboard(User $actor): Response
    {
        $loket = $actor->loket;

        if ($loket === null) {
            return Inertia::render('skpd/inventory', [
                'scope' => 'loket',
                'loket' => null,
                'metrics' => [
                    'received_quantity' => 0,
                    'used_quantity' => 0,
                    'remaining_quantity' => 0,
                    'pending_allocations' => 0,
                ],
                'recent_allocations' => [],
            ]);
        }

        $allocations = SkpdAllocation::query()
            ->whereBelongsTo($loket)
            ->with(['skpdBox:id,box_number', 'usageSegments'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $heldAllocations = $allocations->filter(fn (SkpdAllocation $allocation): bool => $allocation->status->isHeldByLoket());
        $usedQuantity = (int) $heldAllocations
            ->sum(fn (SkpdAllocation $allocation): int => (int) $allocation->usageSegments->sum('quantity'));
        $receivedQuantity = (int) $heldAllocations->sum('quantity');

        return Inertia::render('skpd/inventory', [
            'scope' => 'loket',
            'loket' => ['id' => $loket->id, 'name' => $loket->name],
            'metrics' => [
                'received_quantity' => $receivedQuantity,
                'used_quantity' => $usedQuantity,
                'remaining_quantity' => $receivedQuantity - $usedQuantity,
                'pending_allocations' => $allocations
                    ->where('status', SkpdAllocationStatus::Pending)
                    ->count(),
            ],
            'recent_allocations' => $allocations
                ->take(5)
                ->map(fn (SkpdAllocation $allocation): array => [
                    'id' => $allocation->id,
                    'box_number' => $allocation->skpdBox->box_number,
                    'numerator_start' => $allocation->numerator_start,
                    'numerator_end' => $allocation->numerator_end,
                    'quantity' => $allocation->quantity,
                    'status' => $allocation->status->value,
                    'created_at' => $allocation->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array{id: int, box_number: string, numerator_start: int, numerator_end: int, total_sets: int, pending_quantity: int, allocated_quantity: int, available_quantity: int, used_quantity: int, status: string, loket: array{id: int, name: string}|null, received_at: string}
     */
    private function boxData(SkpdBox $box): array
    {
        $allocations = $box->allocations;
        $pendingQuantity = (int) $allocations
            ->where('status', SkpdAllocationStatus::Pending)
            ->sum('quantity');
        $allocatedQuantity = (int) $allocations
            ->whereIn('status', [SkpdAllocationStatus::Accepted, SkpdAllocationStatus::Completed])
            ->sum('quantity');
        $usedQuantity = (int) $allocations
            ->sum(fn (SkpdAllocation $allocation): int => (int) $allocation->usageSegments->sum('quantity'));
        $activeAllocationQuantity = $pendingQuantity + $allocatedQuantity;
        $status = $activeAllocationQuantity === 0
            ? SkpdBoxStatus::Available
            : ($activeAllocationQuantity < $box->total_sets
                ? SkpdBoxStatus::PartiallyAllocated
                : ($usedQuantity === $box->total_sets ? SkpdBoxStatus::Depleted : SkpdBoxStatus::FullyAllocated));
        $loket = $allocations
            ->whereIn('status', [SkpdAllocationStatus::Pending, SkpdAllocationStatus::Accepted, SkpdAllocationStatus::Completed])
            ->first()?->loket;

        return [
            'id' => $box->id,
            'box_number' => $box->box_number,
            'numerator_start' => $box->numerator_start,
            'numerator_end' => $box->numerator_end,
            'total_sets' => $box->total_sets,
            'pending_quantity' => $pendingQuantity,
            'allocated_quantity' => $allocatedQuantity,
            'available_quantity' => $box->total_sets - $activeAllocationQuantity,
            'used_quantity' => $usedQuantity,
            'status' => $status->value,
            'loket' => $loket === null ? null : ['id' => $loket->id, 'name' => $loket->name],
            'received_at' => $box->received_at->toIso8601String(),
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
