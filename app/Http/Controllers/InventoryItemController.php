<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkInventoryItemPartDamagedRequest;
use App\Http\Requests\ReleaseInventoryItemRequest;
use App\Http\Requests\ReplaceInventoryItemPartRequest;
use App\Http\Requests\StoreInventoryItemPartRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemPartRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemPart;
use App\Models\InventoryItemRelease;
use App\Models\InventoryPartHistory;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAccess();

        $items = InventoryItem::query()
            ->latest()
            ->latest('id')
            ->get();
        $supportsStockQuantity = $this->supportsStockQuantity();
        $supportsReleaseRecords = $this->supportsReleaseRecords();
        $releases = $this->releaseRecords();
        $departments = Department::query()
            ->orderBy('name')
            ->get();
        $sections = Section::query()
            ->with('department')
            ->orderBy('name')
            ->get();

        if ($request->ajax() && $request->query('section') === 'table') {
            return view('inventory-items.partials.table', compact('items', 'supportsStockQuantity'));
        }

        if ($request->ajax() && $request->query('section') === 'releases') {
            return view('inventory-items.partials.release-table', compact('releases'));
        }

        return view('inventory-items.index', [
            'items' => $items,
            'releases' => $releases,
            'supportsStockQuantity' => $supportsStockQuantity,
            'supportsReleaseRecords' => $supportsReleaseRecords,
            'statuses' => InventoryItem::statuses(),
            'departments' => $departments,
            'sections' => $sections,
            'nextItemCode' => $this->nextInventoryItemCode(),
            'inventorySummary' => [
                'total' => $items->count(),
                'stock' => $supportsStockQuantity ? $items->sum('stock_quantity') : 0,
                'active' => $items->where('status', InventoryItem::STATUS_ACTIVE)->count(),
                'released' => $supportsReleaseRecords ? $releases->sum('quantity') : 0,
            ],
        ]);
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = DB::transaction(function () use ($request): InventoryItem {
            $payload = $request->validated();
            $payload['item_code'] = $this->nextInventoryItemCode(true);

            return InventoryItem::create($this->inventoryItemPayload($payload));
        });

        return response()->json([
            'success' => 'Inventory item added successfully.',
            'item' => $item,
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->update($this->inventoryItemPayload($request->safe()->except(['item_code'])));

        return response()->json([
            'success' => 'Inventory item updated successfully.',
            'item' => $inventoryItem->fresh(),
        ]);
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $this->ensureAccess();

        $inventoryItem->delete();

        return response()->json([
            'success' => 'Inventory item deleted successfully.',
        ]);
    }

    public function release(ReleaseInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        if (!$this->supportsStockQuantity() || !$this->supportsReleaseRecords()) {
            return response()->json([
                'message' => 'Inventory release storage is not ready. Please run the latest migrations first.',
            ], 422);
        }

        $payload = $request->validated();

        DB::transaction(function () use ($inventoryItem, $payload): void {
            $lockedItem = InventoryItem::query()
                ->whereKey($inventoryItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payload['quantity'] > $lockedItem->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Only ' . $lockedItem->stock_quantity . ' item(s) are available.'],
                ]);
            }

            $lockedItem->decrement('stock_quantity', $payload['quantity']);

            InventoryItemRelease::create([
                'inventory_item_id' => $lockedItem->id,
                'quantity' => $payload['quantity'],
                'released_to' => auth()->user()?->full_name ?? 'System',
                'department' => $payload['department'] ?? null,
                'location' => $payload['location'] ?? null,
                'purpose' => $payload['purpose'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'released_by' => auth()->user()?->getKey(),
                'released_at' => now(),
            ]);
        });

        return response()->json([
            'success' => 'Inventory item released successfully.',
            'item' => $inventoryItem->fresh(),
        ]);
    }

    public function show(Request $request, InventoryItem $inventoryItem)
    {
        $this->ensureAccess();

        $viewData = $this->buildShowViewData($inventoryItem, $request->string('part_name')->toString());

        if ($request->ajax()) {
            if ($request->query('section') === 'parts') {
                return view('inventory-items.partials.parts-table', $viewData);
            }

            if ($request->query('section') === 'history') {
                return view('inventory-items.partials.history-table', $viewData);
            }
        }

        return view('inventory-items.show', $viewData + [
            'statuses' => InventoryItem::statuses(),
            'departments' => Department::query()->orderBy('name')->get(),
            'sections' => Section::query()->with('department')->orderBy('name')->get(),
            'supportsStockQuantity' => $this->supportsStockQuantity(),
            'supportsReleaseRecords' => $this->supportsReleaseRecords(),
        ]);
    }

    public function storePart(StoreInventoryItemPartRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $payload = $request->validated();
        $status = $payload['status'] ?? InventoryItem::STATUS_ACTIVE;
        $now = now();

        DB::transaction(function () use ($inventoryItem, $payload, $status, $now): void {
            $previousActivePart = null;

            if ($status === InventoryItem::STATUS_ACTIVE) {
                $previousActivePart = InventoryItemPart::query()
                    ->where('inventory_item_id', $inventoryItem->id)
                    ->where('part_name', $payload['part_name'])
                    ->where('status', InventoryItem::STATUS_ACTIVE)
                    ->latest('installed_at')
                    ->latest('id')
                    ->first();
            }

            $newPart = InventoryItemPart::create([
                'inventory_item_id' => $inventoryItem->id,
                'part_name' => $payload['part_name'],
                'serial_number' => $payload['serial_number'] ?? null,
                'brand' => $payload['brand'] ?? null,
                'model' => $payload['model'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'status' => $status,
                'installed_at' => $now,
            ]);

            if ($previousActivePart) {
                $previousActivePart->update([
                    'status' => InventoryItem::STATUS_REPLACED,
                    'removed_at' => $now,
                ]);

                $this->createHistory(
                    inventoryItem: $inventoryItem,
                    partName: $payload['part_name'],
                    actionType: InventoryPartHistory::ACTION_REPLACED,
                    oldPartId: $previousActivePart->id,
                    newPartId: $newPart->id,
                    reason: $payload['replacement_reason'] ?? 'Automatically replaced by a newer installed part.',
                    remarks: $payload['remarks'] ?? null,
                );
            }
        });

        return response()->json([
            'success' => 'Component installed successfully.',
        ]);
    }

    public function updatePart(
        UpdateInventoryItemPartRequest $request,
        InventoryItem $inventoryItem,
        InventoryItemPart $inventoryItemPart
    ): JsonResponse {
        $this->ensurePartBelongsToItem($inventoryItem, $inventoryItemPart);

        $inventoryItemPart->update($request->safe()->only([
            'part_name',
            'serial_number',
            'brand',
            'model',
            'remarks',
        ]));

        return response()->json([
            'success' => 'Component details updated successfully.',
        ]);
    }

    public function destroyPart(InventoryItem $inventoryItem, InventoryItemPart $inventoryItemPart): JsonResponse
    {
        $this->ensurePartBelongsToItem($inventoryItem, $inventoryItemPart);

        $inventoryItemPart->delete();

        return response()->json([
            'success' => 'Component deleted successfully.',
        ]);
    }

    public function markPartAsDamaged(
        MarkInventoryItemPartDamagedRequest $request,
        InventoryItem $inventoryItem,
        InventoryItemPart $inventoryItemPart
    ): JsonResponse {
        $this->ensurePartBelongsToItem($inventoryItem, $inventoryItemPart);

        if ($inventoryItemPart->status !== InventoryItem::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Only active parts can be marked as damaged.',
            ], 422);
        }

        DB::transaction(function () use ($inventoryItem, $inventoryItemPart, $request): void {
            $inventoryItemPart->update([
                'status' => InventoryItem::STATUS_DAMAGED,
            ]);

            $this->createHistory(
                inventoryItem: $inventoryItem,
                partName: $inventoryItemPart->part_name,
                actionType: InventoryPartHistory::ACTION_DAMAGED,
                oldPartId: $inventoryItemPart->id,
                newPartId: null,
                reason: $request->validated('reason'),
                remarks: $request->validated('remarks'),
            );
        });

        return response()->json([
            'success' => 'Component marked as damaged.',
        ]);
    }

    public function replacePart(
        ReplaceInventoryItemPartRequest $request,
        InventoryItem $inventoryItem,
        InventoryItemPart $inventoryItemPart
    ): JsonResponse {
        $this->ensurePartBelongsToItem($inventoryItem, $inventoryItemPart);

        if (in_array($inventoryItemPart->status, [InventoryItem::STATUS_INACTIVE, InventoryItem::STATUS_REPLACED], true)) {
            return response()->json([
                'message' => 'Inactive or replaced parts cannot be replaced again.',
            ], 422);
        }

        $payload = $request->validated();
        $now = now();

        DB::transaction(function () use ($inventoryItem, $inventoryItemPart, $payload, $now): void {
            InventoryItemPart::query()
                ->where('inventory_item_id', $inventoryItem->id)
                ->where('part_name', $payload['part_name'])
                ->where('status', InventoryItem::STATUS_ACTIVE)
                ->whereKeyNot($inventoryItemPart->id)
                ->update([
                    'status' => InventoryItem::STATUS_INACTIVE,
                    'removed_at' => $now,
                    'updated_at' => $now,
                ]);

            $inventoryItemPart->update([
                'status' => InventoryItem::STATUS_REPLACED,
                'removed_at' => $now,
            ]);

            $newPart = InventoryItemPart::create([
                'inventory_item_id' => $inventoryItem->id,
                'part_name' => $payload['part_name'],
                'serial_number' => $payload['serial_number'] ?? null,
                'brand' => $payload['brand'] ?? null,
                'model' => $payload['model'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'status' => InventoryItem::STATUS_ACTIVE,
                'installed_at' => $now,
            ]);

            $this->createHistory(
                inventoryItem: $inventoryItem,
                partName: $payload['part_name'],
                actionType: InventoryPartHistory::ACTION_REPLACED,
                oldPartId: $inventoryItemPart->id,
                newPartId: $newPart->id,
                reason: $payload['reason'],
                remarks: $payload['replacement_remarks'] ?? null,
            );
        });

        return response()->json([
            'success' => 'Component replaced successfully.',
        ]);
    }

    public function history(Request $request, InventoryItem $inventoryItem)
    {
        $this->ensureAccess();

        return view('inventory-items.partials.history-table', $this->buildShowViewData(
            $inventoryItem,
            $request->string('part_name')->toString()
        ));
    }

    protected function buildShowViewData(InventoryItem $inventoryItem, string $partName = ''): array
    {
        $inventoryItem->load(['parts']);
        $allParts = $inventoryItem->parts;
        $activeParts = $allParts
            ->where('status', InventoryItem::STATUS_ACTIVE)
            ->values();

        $damageCounts = InventoryPartHistory::query()
            ->selectRaw('part_name, COUNT(*) as aggregate')
            ->where('inventory_item_id', $inventoryItem->id)
            ->where('action_type', InventoryPartHistory::ACTION_DAMAGED)
            ->groupBy('part_name')
            ->pluck('aggregate', 'part_name');

        $replacementCounts = InventoryPartHistory::query()
            ->selectRaw('part_name, COUNT(*) as aggregate')
            ->where('inventory_item_id', $inventoryItem->id)
            ->where('action_type', InventoryPartHistory::ACTION_REPLACED)
            ->groupBy('part_name')
            ->pluck('aggregate', 'part_name');

        $historyQuery = InventoryPartHistory::query()
            ->with(['oldPart', 'newPart', 'performedBy'])
            ->where('inventory_item_id', $inventoryItem->id)
            ->latest('action_date')
            ->latest('id');

        if ($partName !== '') {
            $historyQuery->where('part_name', $partName);
        }

        return [
            'inventoryItem' => $inventoryItem,
            'parts' => $activeParts,
            'activePartsCount' => $activeParts->count(),
            'damagedPartsCount' => $allParts->where('status', InventoryItem::STATUS_DAMAGED)->count(),
            'replacedPartsCount' => $allParts->where('status', InventoryItem::STATUS_REPLACED)->count(),
            'damageCounts' => $damageCounts,
            'replacementCounts' => $replacementCounts,
            'historyEntries' => $historyQuery->get(),
            'historyPartName' => $partName,
        ];
    }

    protected function createHistory(
        InventoryItem $inventoryItem,
        string $partName,
        string $actionType,
        ?int $oldPartId,
        ?int $newPartId,
        ?string $reason,
        ?string $remarks
    ): void {
        InventoryPartHistory::create([
            'inventory_item_id' => $inventoryItem->id,
            'old_part_id' => $oldPartId,
            'new_part_id' => $newPartId,
            'part_name' => $partName,
            'action_type' => $actionType,
            'reason' => $reason,
            'remarks' => $remarks,
            'action_date' => now(),
            'performed_by' => auth()->user()?->getKey(),
        ]);
    }

    protected function releaseRecords()
    {
        if (!$this->supportsReleaseRecords()) {
            return collect();
        }

        return InventoryItemRelease::query()
            ->with(['inventoryItem', 'releasedBy'])
            ->latest('released_at')
            ->latest('id')
            ->limit(100)
            ->get();
    }

    protected function inventoryItemPayload(array $payload): array
    {
        if (!$this->supportsStockQuantity()) {
            unset($payload['stock_quantity']);
        }

        return $payload;
    }

    protected function nextInventoryItemCode(bool $lockForUpdate = false): string
    {
        $query = InventoryItem::query()
            ->where('item_code', 'like', 'MIS-%');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $highestNumber = $query
            ->pluck('item_code')
            ->reduce(function (int $highest, string $itemCode): int {
                if (preg_match('/^MIS-(\d+)$/i', $itemCode, $matches) !== 1) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return 'MIS-' . ($highestNumber + 1);
    }

    protected function supportsStockQuantity(): bool
    {
        return Schema::hasColumn('inventory_items', 'stock_quantity');
    }

    protected function supportsReleaseRecords(): bool
    {
        return Schema::hasTable('inventory_item_releases');
    }

    protected function ensureAccess(): void
    {
        abort_unless(auth()->user()?->canManageInventory(), 403);
    }

    protected function ensurePartBelongsToItem(InventoryItem $inventoryItem, InventoryItemPart $inventoryItemPart): void
    {
        $this->ensureAccess();

        abort_unless($inventoryItemPart->inventory_item_id === $inventoryItem->id, 404);
    }
}
