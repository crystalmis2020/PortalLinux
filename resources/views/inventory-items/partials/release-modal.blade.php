<div class="modal fade" id="releaseInventoryItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="releaseInventoryItemForm">
            @csrf
            <input type="hidden" id="release_inventory_item_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="releaseInventoryItemModalTitle">Release Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2" id="releaseInventoryItemStockNote"></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="release_quantity" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select class="form-select inventory-department-select" name="department" id="release_department" data-section-target="#release_location">
                                <option value="">Select Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->name }}" data-department-id="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Location / Section</label>
                            <select class="form-select inventory-section-select" name="location" id="release_location">
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
                            <label class="form-label">Purpose</label>
                            <input type="text" class="form-control" name="purpose" id="purpose">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" rows="3" name="remarks" id="release_remarks"></textarea>
                        </div>
                    </div>
                    <div class="alert alert-danger mt-3 d-none" id="releaseInventoryItemFormAlert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="releaseInventoryItemSubmitButton">Release Item</button>
                </div>
            </div>
        </form>
    </div>
</div>
