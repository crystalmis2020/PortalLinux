@extends('layout.app')

@section('css-custom')
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .inventory-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            box-shadow: none;
        }

        .inventory-code {
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .inventory-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0;
            margin-bottom: 18px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .inventory-summary__item {
            padding: 16px;
            border-right: 1px solid rgba(15, 23, 42, 0.08);
            background: transparent;
        }

        .inventory-summary__item:last-child {
            border-right: 0;
        }

        .inventory-summary__label {
            margin-bottom: 6px;
            color: #6c757d;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .inventory-summary__value {
            margin-bottom: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #172033;
        }

        .inventory-section-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .inventory-table-shell .card-header,
        .inventory-table-shell .card-body {
            padding: 16px;
        }

        .inventory-table-shell .card-header {
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }

        .inventory-table-shell table.dataTable {
            border-collapse: collapse !important;
        }

        .inventory-table-shell .table > :not(caption) > * > * {
            padding: 0.78rem 0.75rem;
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }

        .inventory-table-shell thead th {
            color: #64748b;
            font-size: 0.74rem;
            text-transform: uppercase;
            white-space: nowrap;
            background: transparent;
        }

        html.dark-theme .inventory-summary__item {
            border-color: rgba(255, 255, 255, 0.08);
        }

        html.dark-theme .inventory-summary,
        html.dark-theme .inventory-card {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
        }

        html.dark-theme .inventory-summary__value {
            color: #f4f7fb;
        }

        html.dark-theme #inventoryItemModal .form-select,
        html.dark-theme #releaseInventoryItemModal .form-select {
            color: #f4f7fb;
            background-color: #242a2f;
            border-color: rgba(255, 255, 255, 0.16);
            color-scheme: dark;
        }

        html.dark-theme #inventoryItemModal .form-select option,
        html.dark-theme #releaseInventoryItemModal .form-select option {
            color: #f4f7fb;
            background-color: #242a2f;
        }

        html.dark-theme .inventory-card .form-select,
        html.dark-theme .inventory-card .form-control,
        html.dark-theme #inventoryItemModal .form-select,
        html.dark-theme #inventoryItemModal .form-control,
        html.dark-theme #releaseInventoryItemModal .form-select,
        html.dark-theme #releaseInventoryItemModal .form-control {
            color: #f4f7fb;
            background-color: #242a2f;
            border-color: rgba(255, 255, 255, 0.16);
            color-scheme: dark;
        }

        html.dark-theme .inventory-card .form-select,
        html.dark-theme #inventoryItemModal .form-select,
        html.dark-theme #releaseInventoryItemModal .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23f4f7fb' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        }

        html.dark-theme .inventory-card .form-select option,
        html.dark-theme #inventoryItemModal .form-select option,
        html.dark-theme #releaseInventoryItemModal .form-select option {
            color: #f4f7fb;
            background-color: #242a2f;
        }

        html.dark-theme .inventory-card .dataTables_length,
        html.dark-theme .inventory-card .dataTables_filter,
        html.dark-theme .inventory-card .dataTables_info {
            color: #d7e2dc;
        }

        html.dark-theme .inventory-card .page-link {
            color: #d7e2dc;
            background-color: #242a2f;
            border-color: rgba(255, 255, 255, 0.12);
        }

        html.dark-theme .inventory-card .page-item.active .page-link {
            color: #07130f;
            background-color: #5ce0b8;
            border-color: #5ce0b8;
        }

        @media (max-width: 991.98px) {
            .inventory-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .inventory-summary__item:nth-child(2) {
                border-right: 0;
            }

            .inventory-summary__item:nth-child(-n + 2) {
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }
        }

        @media (max-width: 575.98px) {
            .inventory-summary {
                grid-template-columns: 1fr;
            }

            .inventory-summary__item {
                border-right: 0;
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }

            .inventory-summary__item:last-child {
                border-bottom: 0;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">MIS Item Inventory</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Inventory Items</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="inventory-summary">
        <div class="inventory-summary__item">
            <p class="inventory-summary__label">Total Items</p>
            <p class="inventory-summary__value" id="inventorySummaryTotal">{{ $inventorySummary['total'] }}</p>
        </div>
        <div class="inventory-summary__item">
            <p class="inventory-summary__label">Stored Qty</p>
            <p class="inventory-summary__value text-success" id="inventorySummaryStock">{{ $inventorySummary['stock'] }}</p>
        </div>
        <div class="inventory-summary__item">
            <p class="inventory-summary__label">Active</p>
            <p class="inventory-summary__value text-primary" id="inventorySummaryActive">{{ $inventorySummary['active'] }}</p>
        </div>
        <div class="inventory-summary__item">
            <p class="inventory-summary__label">Released Qty</p>
            <p class="inventory-summary__value text-danger" id="inventorySummaryReleased">{{ $inventorySummary['released'] }}</p>
        </div>
    </div>

    <div class="card inventory-card inventory-table-shell">
        <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="inventory-section-title mb-1">Inventory Stock Registry</h5>
                <p class="text-muted mb-0 small">Track stored quantities, releases, and assigned locations.</p>
            </div>
            <button
                type="button"
                class="btn btn-outline-primary"
                id="addInventoryItemButton"
                data-bs-toggle="modal"
                data-bs-target="#inventoryItemModal">
                <i class="bx bx-plus me-1"></i>Add Item
            </button>
        </div>
        <div class="card-body">
            <div id="inventoryItemsTableWrapper">
                @include('inventory-items.partials.table', [
                    'items' => $items,
                    'supportsStockQuantity' => $supportsStockQuantity,
                ])
            </div>
        </div>
    </div>

    @if ($supportsReleaseRecords)
        <div class="card inventory-card inventory-table-shell mt-4">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="inventory-section-title mb-1">Release Transaction Records</h5>
                    <p class="text-muted mb-0 small">Audit trail of released inventory quantities.</p>
                </div>
            </div>
            <div class="card-body">
                <div id="inventoryReleasesTableWrapper">
                    @include('inventory-items.partials.release-table', ['releases' => $releases])
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning mt-4">
            Inventory release records are not ready yet. Run the latest migrations to enable release tracking.
        </div>
    @endif

    <div class="modal fade" id="inventoryItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="inventoryItemForm" data-create-url="{{ route('inventory-items.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="item_id" id="inventory_item_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="inventoryItemModalTitle">Add Inventory Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Item Code</label>
                                <input type="text" class="form-control" id="item_code" value="{{ $nextItemCode }}" data-next-code="{{ $nextItemCode }}" readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="item_name" id="item_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stored Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Assigned To</label>
                                <input type="text" class="form-control" name="assigned_to" id="assigned_to">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="status">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select class="form-select inventory-department-select" name="department" id="department" data-section-target="#location">
                                    <option value="">Select Department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->name }}" data-department-id="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Section</label>
                                <select class="form-select inventory-section-select" name="location" id="location">
                                    <option value="">Select Section</option>
                                    @foreach ($sections as $section)
                                        <option
                                            value="{{ $section->name }}"
                                            data-department-id="{{ $section->department_id }}"
                                            data-department-name="{{ $section->department?->name }}">
                                            {{ $section->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" rows="3" name="remarks" id="remarks"></textarea>
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3 d-none" id="inventoryItemFormAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="inventoryItemSubmitButton">Save Item</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('inventory-items.partials.release-modal', [
        'departments' => $departments,
        'sections' => $sections,
    ])

    <div class="modal fade" id="deleteInventoryItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="deleteInventoryItemForm">
                @csrf
                <input type="hidden" id="delete_inventory_item_url">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Inventory Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Delete <span class="fw-semibold" id="delete_inventory_item_name"></span>?</p>
                        <div class="alert alert-danger mt-3 d-none" id="deleteInventoryItemFormAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="deleteInventoryItemSubmitButton">Delete</button>
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
