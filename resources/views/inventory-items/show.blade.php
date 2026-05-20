@extends('layout.app')

@section('css-custom')
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .inventory-workspace {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            background: #fff;
            box-shadow: none;
        }

        .inventory-meta-label {
            font-size: 0.74rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #6c757d;
        }

        .inventory-workspace__header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(260px, 360px);
            gap: 18px;
            padding: 18px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .inventory-title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .inventory-title-row h4 {
            margin-bottom: 0;
        }

        .inventory-meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .grid-column-wide {
            grid-column: span 2;
        }

        .inventory-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            overflow: hidden;
            background: rgba(248, 250, 252, 0.72);
        }

        .inventory-stat {
            padding: 14px;
            border-right: 1px solid rgba(15, 23, 42, 0.08);
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .inventory-stat:nth-child(2n) {
            border-right: 0;
        }

        .inventory-stat:nth-last-child(-n + 2) {
            border-bottom: 0;
        }

        .inventory-stat__label {
            margin-bottom: 6px;
            color: #6c757d;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .inventory-stat__value {
            margin-bottom: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #172033;
        }

        .inventory-section-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .inventory-workspace__tabs {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 18px 0;
        }

        .inventory-workspace__tabs .nav-tabs {
            border-bottom: 0;
            gap: 6px;
        }

        .inventory-workspace__tabs .nav-link {
            border: 1px solid transparent;
            border-radius: 7px;
            color: #64748b;
            font-weight: 700;
            padding: 8px 12px;
        }

        .inventory-workspace__tabs .nav-link.active {
            color: #172033;
            background: rgba(15, 23, 42, 0.05);
            border-color: rgba(15, 23, 42, 0.08);
        }

        .inventory-tab-body {
            padding: 16px 18px 18px;
        }

        .inventory-workspace table.dataTable {
            border-collapse: collapse !important;
        }

        .inventory-workspace .table > :not(caption) > * > * {
            padding: 0.78rem 0.75rem;
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }

        .inventory-workspace thead th {
            color: #64748b;
            font-size: 0.74rem;
            text-transform: uppercase;
            white-space: nowrap;
            background: transparent;
        }

        .inventory-workspace .table-responsive {
            overflow: visible;
        }

        .inventory-workspace .dropdown-menu {
            z-index: 1080;
        }

        html.dark-theme .inventory-workspace,
        html.dark-theme .inventory-stat-grid {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
        }

        html.dark-theme .inventory-workspace__header,
        html.dark-theme .inventory-stat,
        html.dark-theme .inventory-workspace .table > :not(caption) > * > * {
            border-color: rgba(255, 255, 255, 0.08);
        }

        html.dark-theme .inventory-stat__value,
        html.dark-theme .inventory-workspace__tabs .nav-link.active {
            color: #f4f7fb;
        }

        html.dark-theme .inventory-workspace__tabs .nav-link.active {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.08);
        }

        html.dark-theme #inventoryPartModal .form-select,
        html.dark-theme #releaseInventoryItemModal .form-select {
            color: #f4f7fb;
            background-color: #242a2f;
            border-color: rgba(255, 255, 255, 0.16);
            color-scheme: dark;
        }

        html.dark-theme #inventoryPartModal .form-select option,
        html.dark-theme #releaseInventoryItemModal .form-select option {
            color: #f4f7fb;
            background-color: #242a2f;
        }

        html.dark-theme .inventory-workspace .form-select,
        html.dark-theme .inventory-workspace .form-control,
        html.dark-theme #inventoryPartModal .form-select,
        html.dark-theme #inventoryPartModal .form-control,
        html.dark-theme #releaseInventoryItemModal .form-select,
        html.dark-theme #releaseInventoryItemModal .form-control,
        html.dark-theme #damagePartModal .form-control,
        html.dark-theme #replacePartModal .form-control {
            color: #f4f7fb;
            background-color: #242a2f;
            border-color: rgba(255, 255, 255, 0.16);
            color-scheme: dark;
        }

        html.dark-theme .inventory-workspace .form-select,
        html.dark-theme #inventoryPartModal .form-select,
        html.dark-theme #releaseInventoryItemModal .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23f4f7fb' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        }

        html.dark-theme .inventory-workspace .form-select option,
        html.dark-theme #inventoryPartModal .form-select option,
        html.dark-theme #releaseInventoryItemModal .form-select option {
            color: #f4f7fb;
            background-color: #242a2f;
        }

        html.dark-theme .inventory-workspace .dataTables_length,
        html.dark-theme .inventory-workspace .dataTables_filter,
        html.dark-theme .inventory-workspace .dataTables_info {
            color: #d7e2dc;
        }

        html.dark-theme .inventory-workspace .page-link {
            color: #d7e2dc;
            background-color: #242a2f;
            border-color: rgba(255, 255, 255, 0.12);
        }

        html.dark-theme .inventory-workspace .page-item.active .page-link {
            color: #07130f;
            background-color: #5ce0b8;
            border-color: #5ce0b8;
        }

        @media (max-width: 991.98px) {
            .inventory-workspace__header,
            .inventory-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .grid-column-wide {
                grid-column: span 2;
            }
        }

        @media (max-width: 575.98px) {
            .inventory-workspace .table-responsive {
                overflow-x: auto;
            }

            .inventory-workspace__header,
            .inventory-meta-grid,
            .inventory-stat-grid {
                grid-template-columns: 1fr;
            }

            .grid-column-wide {
                grid-column: span 1;
            }

            .inventory-stat {
                border-right: 0;
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }

            .inventory-stat:last-child {
                border-bottom: 0;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Inventory Item</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory-items.index') }}">Inventory Items</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $inventoryItem->item_code }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="inventory-workspace">
        <div class="inventory-workspace__header">
            <div>
                <div class="inventory-title-row">
                    <div>
                        <p class="inventory-meta-label mb-1">Inventory Item</p>
                        <h4>{{ $inventoryItem->item_code }}</h4>
                    </div>
                    @include('inventory-items.partials.status-badge', ['status' => $inventoryItem->status])
                </div>
                <p class="text-muted mb-3">{{ $inventoryItem->item_name }}</p>
                <div class="inventory-meta-grid">
                    <div>
                        <p class="inventory-meta-label mb-1">Assigned To</p>
                        <p class="mb-0">{{ $inventoryItem->assigned_to ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="inventory-meta-label mb-1">Stored Quantity</p>
                        <p class="mb-0 fw-semibold" id="inventoryItemStockQuantity">{{ $inventoryItem->stock_quantity }}</p>
                    </div>
                    <div>
                        <p class="inventory-meta-label mb-1">Department</p>
                        <p class="mb-0">{{ $inventoryItem->department ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="inventory-meta-label mb-1">Location</p>
                        <p class="mb-0">{{ $inventoryItem->location ?: '—' }}</p>
                    </div>
                    <div class="grid-column-wide">
                        <p class="inventory-meta-label mb-1">Remarks</p>
                        <p class="mb-0">{{ $inventoryItem->remarks ?: '—' }}</p>
                    </div>
                </div>
            </div>
            <div>
                <div class="inventory-stat-grid">
                    <div class="inventory-stat">
                        <p class="inventory-stat__label">Components</p>
                        <p class="inventory-stat__value" id="inventoryPartsSummaryTotal">{{ $parts->count() }}</p>
                    </div>
                    <div class="inventory-stat">
                        <p class="inventory-stat__label">Active</p>
                        <p class="inventory-stat__value text-success" id="inventoryPartsSummaryActive">{{ $activePartsCount }}</p>
                    </div>
                    <div class="inventory-stat">
                        <p class="inventory-stat__label">Damaged</p>
                        <p class="inventory-stat__value text-danger" id="inventoryPartsSummaryDamaged">{{ $damagedPartsCount }}</p>
                    </div>
                    <div class="inventory-stat">
                        <p class="inventory-stat__label">History</p>
                        <p class="inventory-stat__value text-primary" id="inventoryPartsSummaryHistory">{{ $historyEntries->count() }}</p>
                    </div>
                </div>
                <div class="d-grid gap-2 mt-3">
                    <button
                        type="button"
                        class="btn btn-outline-success release-inventory-item"
                        data-id="{{ $inventoryItem->id }}"
                        data-release-url="{{ route('inventory-items.release', $inventoryItem) }}"
                        data-item='@json($inventoryItem)'
                        {{ (!$supportsStockQuantity || !$supportsReleaseRecords || ($inventoryItem->stock_quantity ?? 0) < 1) ? 'disabled' : '' }}>
                        <i class="bx bx-log-out-circle me-1"></i>Release Item
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="addInventoryPartButton"
                        data-bs-toggle="modal"
                        data-bs-target="#inventoryPartModal">
                        <i class="bx bx-plus me-1"></i>Install Component
                    </button>
                </div>
            </div>
        </div>

        <div class="inventory-workspace__tabs">
            <ul class="nav nav-tabs" id="inventoryItemTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="inventoryPartsTab" data-bs-toggle="tab" data-bs-target="#inventoryPartsPane" type="button" role="tab" aria-controls="inventoryPartsPane" aria-selected="true">
                        Components
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inventoryHistoryTab" data-bs-toggle="tab" data-bs-target="#inventoryHistoryPane" type="button" role="tab" aria-controls="inventoryHistoryPane" aria-selected="false">
                        History
                    </button>
                </li>
            </ul>
            <span class="text-muted small" id="historyLabel">
                {{ $historyPartName !== '' ? 'Filtered: ' . $historyPartName : 'Showing all component history' }}
            </span>
        </div>

        <div class="tab-content inventory-tab-body">
            <div class="tab-pane fade show active" id="inventoryPartsPane" role="tabpanel" aria-labelledby="inventoryPartsTab" tabindex="0">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="inventory-section-title mb-0">Active Components</h5>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#inventoryPartModal"
                        id="addInventoryPartButtonInline">
                        <i class="bx bx-plus me-1"></i>Install Component
                    </button>
                </div>
                <div id="inventoryPartsTableWrapper">
                    @include('inventory-items.partials.parts-table', [
                        'inventoryItem' => $inventoryItem,
                        'parts' => $parts,
                        'activePartsCount' => $activePartsCount,
                        'damagedPartsCount' => $damagedPartsCount,
                        'damageCounts' => $damageCounts,
                        'replacementCounts' => $replacementCounts,
                    ])
                </div>
            </div>
            <div class="tab-pane fade" id="inventoryHistoryPane" role="tabpanel" aria-labelledby="inventoryHistoryTab" tabindex="0">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="inventory-section-title mb-0">Component History</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearPartHistoryFilter">
                        <i class="bx bx-list-ul me-1"></i>Show All
                    </button>
                </div>
                <div id="inventoryHistoryTableWrapper">
                    @include('inventory-items.partials.history-table', [
                        'historyEntries' => $historyEntries,
                        'historyPartName' => $historyPartName,
                    ])
                </div>
            </div>
        </div>
    </div>

    @include('inventory-items.partials.release-modal', [
        'departments' => $departments,
        'sections' => $sections,
    ])

    <div class="modal fade" id="inventoryPartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form
                id="inventoryPartForm"
                data-create-url="{{ route('inventory-items.parts.store', $inventoryItem) }}"
                data-item-id="{{ $inventoryItem->id }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="inventoryPartModalTitle">Install Component</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="part_id" id="part_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Component Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="part_name" id="part_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Serial Number</label>
                                <input type="text" class="form-control" name="serial_number" id="serial_number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Brand</label>
                                <input type="text" class="form-control" name="brand" id="part_brand">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" id="part_model">
                            </div>
                            <input type="hidden" name="status" id="part_status" value="{{ \App\Models\InventoryItem::STATUS_ACTIVE }}">
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" rows="3" name="remarks" id="part_remarks"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Installation Note</label>
                                <input type="text" class="form-control" name="replacement_reason" id="replacement_reason">
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3 d-none" id="inventoryPartFormAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="inventoryPartSubmitButton">Save Component</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="damagePartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="damagePartForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Mark Component as Damaged</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="damage_url" id="damage_url">
                        <div class="mb-3">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reason" id="damage_reason">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" rows="3" name="remarks" id="damage_remarks"></textarea>
                        </div>
                        <div class="alert alert-danger mt-3 d-none" id="damagePartFormAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="damagePartSubmitButton">Save Damage Record</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="replacePartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="replacePartForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Replace Component</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="replace_url" id="replace_url">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Component Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="part_name" id="replace_part_name" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Serial Number</label>
                                <input type="text" class="form-control" name="serial_number" id="replace_serial_number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Brand</label>
                                <input type="text" class="form-control" name="brand" id="replace_brand">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" id="replace_model">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="reason" id="replace_reason">
                            </div>
                            <div class="col-12">
                                <label class="form-label">New Component Remarks</label>
                                <textarea class="form-control" rows="3" name="remarks" id="replace_part_remarks"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Replacement History Note</label>
                                <textarea class="form-control" rows="3" name="replacement_remarks" id="replacement_remarks"></textarea>
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3 d-none" id="replacePartFormAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="replacePartSubmitButton">Replace Component</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deletePartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="deletePartForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Component</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="delete_part_url">
                        <p class="mb-2">Delete <strong id="delete_part_name">this component</strong>?</p>
                        <p class="text-muted mb-0">Use delete only for mistaken component records. Use damage or replace for real maintenance events.</p>
                        <div class="alert alert-danger mt-3 d-none" id="deletePartFormAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="deletePartSubmitButton">Delete Component</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js-custom')
    <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/js/custom/inventory-items.js') }}"></script>
@endsection
