$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const currentPath = window.location.pathname;

    function notify(type, message) {
        const alertType = type === 'success' ? 'success' : 'danger';
        const $pageContent = $('.page-content').first();
        const $feedback = $('<div>')
            .addClass('alert alert-' + alertType + ' inventory-page-feedback')
            .attr('role', 'alert')
            .text(message);

        $('.inventory-page-feedback').remove();
        $pageContent.prepend($feedback);
        setTimeout(function () {
            $feedback.fadeOut(200, function () {
                $(this).remove();
            });
        }, 5000);

        if (window.Lobibox) {
            Lobibox.notify(type, {
                size: 'mini',
                rounded: true,
                delayIndicator: true,
                sound: false,
                position: 'top right',
                msg: message
            });
            return;
        }

        window.alert(message);
    }

    function clearValidation($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.dynamic-feedback').remove();
        $form.find('.alert-danger').addClass('d-none').empty();
    }

    function showValidationErrors($form, errors) {
        clearValidation($form);

        const messages = [];

        Object.keys(errors).forEach(function (fieldName) {
            const $field = $form.find('[name="' + fieldName + '"]');
            const message = errors[fieldName][0];
            messages.push(message);

            if ($field.length) {
                $field.addClass('is-invalid');
                $('<div class="invalid-feedback dynamic-feedback"></div>')
                    .text(message)
                    .insertAfter($field);
            }
        });

        if (messages.length) {
            $form.find('.alert').removeClass('d-none').html(messages.join('<br>'));
        }
    }

    function reinitializeTable(tableId, defaultOrder) {
        const selector = '#' + tableId;
        if (!$(selector).length) {
            return;
        }

        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().destroy();
        }

        $(selector).DataTable({
            pageLength: 10,
            order: defaultOrder,
            language: {
                emptyTable: tableId === 'inventoryItemsTable'
                    ? 'No inventory items yet.'
                    : (tableId === 'inventoryPartsTable'
                        ? 'No active components found for this item.'
                        : (tableId === 'inventoryReleasesTable'
                            ? 'No release transaction records found.'
                            : 'No history records found.')),
                zeroRecords: 'No matching records found.'
            }
        });
    }

    function adjustVisibleTables() {
        ['inventoryItemsTable', 'inventoryPartsTable', 'inventoryHistoryTable', 'inventoryReleasesTable'].forEach(function (tableId) {
            const selector = '#' + tableId;
            if ($(selector).length && $.fn.DataTable.isDataTable(selector)) {
                $(selector).DataTable().columns.adjust();
            }
        });
    }

    function initializeTooltips() {
        if (!window.bootstrap?.Tooltip) {
            return;
        }

        $('[data-bs-toggle="tooltip"]').each(function () {
            bootstrap.Tooltip.getOrCreateInstance(this);
        });
    }

    function syncSectionOptions($departmentSelect) {
        const departmentId = String($departmentSelect.find('option:selected').data('department-id') || '');
        const departmentName = String($departmentSelect.val() || '');
        const $sectionSelect = $($departmentSelect.data('section-target'));

        if (!$sectionSelect.length) {
            return;
        }

        $sectionSelect.find('option').each(function () {
            const $option = $(this);

            if (!$option.val()) {
                $option.prop('hidden', false).prop('disabled', false);
                return;
            }

            const optionDepartmentId = String($option.data('department-id') || '');
            const optionDepartmentName = String($option.data('department-name') || '');
            const isVisible = !departmentId || optionDepartmentId === departmentId || optionDepartmentName === departmentName;

            $option.prop('hidden', !isVisible).prop('disabled', !isVisible);
        });

        if ($sectionSelect.find('option:selected').prop('disabled')) {
            $sectionSelect.val('');
        }
    }

    function setDepartmentAndSection(departmentSelector, sectionSelector, department, section) {
        const $department = $(departmentSelector);
        const $section = $(sectionSelector);

        $department.val(department || '');
        syncSectionOptions($department);
        $section.val(section || '');

        if ($section.find('option:selected').prop('disabled')) {
            $section.val('');
        }
    }

    function advanceNextItemCode(itemCode) {
        const match = String(itemCode || '').match(/^MIS-(\d+)$/i);

        if (!match) {
            return;
        }

        $('#item_code').data('next-code', 'MIS-' + (Number(match[1]) + 1));
    }

    function tableBodyRows(tableId) {
        return $('#' + tableId + ' tbody tr');
    }

    function updateInventorySummaryFromTable() {
        if (!$('#inventorySummaryTotal').length || !$('#inventoryItemsTable').length) {
            return;
        }

        const $rows = tableBodyRows('inventoryItemsTable');
        const activeCount = $rows.filter(function () {
            const item = $(this).find('.edit-inventory-item').data('item');
            return item?.status === 'active';
        }).length;
        const stockCount = $rows.toArray().reduce(function (total, row) {
            const quantity = Number($(row).find('td:eq(1)').text().trim());
            return total + (Number.isNaN(quantity) ? 0 : quantity);
        }, 0);
        const releasedCount = tableBodyRows('inventoryReleasesTable').toArray().reduce(function (total, row) {
            const quantity = Number($(row).find('td:eq(2)').text().trim());
            return total + (Number.isNaN(quantity) ? 0 : quantity);
        }, 0);
        $('#inventorySummaryTotal').text($rows.length);
        $('#inventorySummaryActive').text(activeCount);
        $('#inventorySummaryStock').text(stockCount);
        $('#inventorySummaryReleased').text(releasedCount);
    }

    function updatePartSummaryFromTables() {
        if (!$('#inventoryPartsSummaryTotal').length) {
            return;
        }

        const $partsTable = $('.inventory-parts-table').first();
        const $partRows = tableBodyRows('inventoryPartsTable');
        const activeParts = $partRows.filter(function () {
            return $(this).find('td:eq(2) .badge').text().trim().toLowerCase() === 'active';
        }).length;
        const totalCount = Number($partsTable.data('total-count'));
        const activeCount = Number($partsTable.data('active-count'));
        const damagedCount = Number($partsTable.data('damaged-count'));

        $('#inventoryPartsSummaryTotal').text(Number.isNaN(totalCount) ? $partRows.length : totalCount);
        $('#inventoryPartsSummaryActive').text(Number.isNaN(activeCount) ? activeParts : activeCount);
        $('#inventoryPartsSummaryDamaged').text(Number.isNaN(damagedCount) ? 0 : damagedCount);
        $('#inventoryPartsSummaryHistory').text(tableBodyRows('inventoryHistoryTable').length);
    }

    function refreshInventoryItemsTable() {
        if (!$('#inventoryItemsTableWrapper').length) {
            return;
        }

        const indexUrl = currentPath;
        $.get(indexUrl, { section: 'table' })
            .done(function (html) {
                $('#inventoryItemsTableWrapper').html(html);
                updateInventorySummaryFromTable();
                reinitializeTable('inventoryItemsTable', []);
                initializeTooltips();
            })
            .fail(function () {
                notify('error', 'Unable to refresh inventory items.');
            });
    }

    function updateCurrentInventoryItem(item) {
        if (!item || !$('#inventoryItemStockQuantity').length) {
            return;
        }

        const stockQuantity = Number(item.stock_quantity || 0);
        $('#inventoryItemStockQuantity').text(stockQuantity);

        $('.release-inventory-item[data-id="' + item.id + '"]').each(function () {
            const $button = $(this);
            $button.data('item', item);
            $button.prop('disabled', stockQuantity < 1);
        });
    }

    function refreshInventoryReleasesTable() {
        if (!$('#inventoryReleasesTableWrapper').length) {
            return;
        }

        $.get(currentPath, { section: 'releases' })
            .done(function (html) {
                $('#inventoryReleasesTableWrapper').html(html);
                reinitializeTable('inventoryReleasesTable', [[0, 'desc']]);
                updateInventorySummaryFromTable();
                initializeTooltips();
            })
            .fail(function () {
                notify('error', 'Unable to refresh release records.');
            });
    }

    function refreshItemDetails(partName) {
        const baseUrl = currentPath;

        $.get(baseUrl, { section: 'parts' })
            .done(function (html) {
                $('#inventoryPartsTableWrapper').html(html);
                updatePartSummaryFromTables();
                reinitializeTable('inventoryPartsTable', [[0, 'asc']]);
                initializeTooltips();
            })
            .fail(function () {
                notify('error', 'Unable to refresh item parts.');
            });

        const historyParams = { section: 'history' };
        if (partName) {
            historyParams.part_name = partName;
        }

        $.get(baseUrl, historyParams)
            .done(function (html) {
                $('#inventoryHistoryTableWrapper').html(html);
                $('#historyLabel').text(partName ? 'Filtered: ' + partName : 'Showing all component history');
                updatePartSummaryFromTables();
                reinitializeTable('inventoryHistoryTable', [[0, 'desc']]);
                initializeTooltips();
            })
            .fail(function () {
                notify('error', 'Unable to refresh component history.');
            });
    }

    function submitAjaxForm(options) {
        const $form = options.form;
        const $button = options.button;
        const buttonText = $button.text();

        clearValidation($form);
        $button.prop('disabled', true).text(options.loadingText || 'Saving...');

        $.ajax({
            url: options.url,
            method: options.method || 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: options.data,
            success: function (response) {
                notify('success', response.success || 'Saved successfully.');
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(response);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                    notify('error', 'Please fix the highlighted fields.');
                } else {
                    notify('error', xhr.responseJSON?.message || 'Something went wrong.');
                }
            },
            complete: function () {
                $button.prop('disabled', false).text(buttonText);
            }
        });
    }

    function resetInventoryItemModal() {
        const $form = $('#inventoryItemForm');
        $form[0].reset();
        $form.find('input[name="_method"]').val('POST');
        $('#inventory_item_id').val('');
        $('#inventoryItemModalTitle').text('Add Inventory Item');
        $('#item_code').val($('#item_code').data('next-code') || '');
        $('#stock_quantity').val(0);
        syncSectionOptions($('#department'));
        clearValidation($form);
    }

    function resetReleaseInventoryItemModal() {
        const $form = $('#releaseInventoryItemForm');
        $form[0].reset();
        $('#release_inventory_item_id').val('');
        $('#releaseInventoryItemStockNote').empty();
        syncSectionOptions($('#release_department'));
        clearValidation($form);
    }

    function resetEditReleaseRecordModal() {
        const $form = $('#editReleaseRecordForm');
        $form[0].reset();
        $('#editReleaseRecordItemNote').empty();
        syncSectionOptions($('#edit_release_department'));
        clearValidation($form);
    }

    function resetInventoryPartModal() {
        const $form = $('#inventoryPartForm');
        $form[0].reset();
        $form.find('input[name="_method"]').val('POST');
        $('#inventoryPartModalTitle').text('Install Component');
        $('#part_status').val('active').prop('disabled', false);
        $('#replacement_reason').prop('disabled', false);
        clearValidation($form);
    }

    $(document).on('shown.bs.modal', '#inventoryItemModal', function () {
        if (!$('#inventory_item_id').val()) {
            $('#item_name').trigger('focus');
        }
    });

    $('#addInventoryItemButton').on('click', function () {
        resetInventoryItemModal();
    });

    $(document).on('change', '.inventory-department-select', function () {
        syncSectionOptions($(this));
    });

    $(document).on('click', '.edit-inventory-item', function () {
        resetInventoryItemModal();

        const item = $(this).data('item');
        const updateUrl = $(this).data('update-url');
        const $form = $('#inventoryItemForm');

        $('#inventory_item_id').val(item.id);
        $('#item_code').val(item.item_code);
        $('#item_name').val(item.item_name);
        $('#stock_quantity').val(item.stock_quantity ?? 0);
        $('#assigned_to').val(item.assigned_to);
        setDepartmentAndSection('#department', '#location', item.department, item.location);
        $('#remarks').val(item.remarks);
        $('#status').val(item.status);
        $('#inventoryItemModalTitle').text('Edit Inventory Item');
        $form.data('update-url', updateUrl);
        $form.find('input[name="_method"]').val('PUT');

        new bootstrap.Modal(document.getElementById('inventoryItemModal')).show();
	    });

    $(document).on('click', '.release-inventory-item', function () {
        resetReleaseInventoryItemModal();

        const item = $(this).data('item');
        const releaseUrl = $(this).data('release-url');
        const stockQuantity = Number(item.stock_quantity || 0);
        const $form = $('#releaseInventoryItemForm');

        $('#release_inventory_item_id').val(item.id);
        $('#release_quantity').attr('max', stockQuantity).val(stockQuantity > 0 ? 1 : 0);
        setDepartmentAndSection('#release_department', '#release_location', item.department, item.location);
        $('#releaseInventoryItemModalTitle').text('Release ' + item.item_code);
        $('#releaseInventoryItemStockNote').text(item.item_name + ' has ' + stockQuantity + ' item(s) currently stored.');
        $form.data('release-url', releaseUrl);

        new bootstrap.Modal(document.getElementById('releaseInventoryItemModal')).show();
    });

    $(document).on('click', '.view-inventory-item-history', function () {
        const item = $(this).data('item');
        const stockQuantity = Number(item.stock_quantity || 0);
        const releasedQuantity = Number(item.released_quantity || 0);
        const originalQuantity = stockQuantity + releasedQuantity;
        const valueOrDash = function (value) {
            return value || '—';
        };

        $('#history_item_code').text(valueOrDash(item.item_code));
        $('#history_item_name').text(valueOrDash(item.item_name));
        $('#history_original_quantity').text(originalQuantity);
        $('#history_stock_quantity').text(stockQuantity);
        $('#history_released_quantity').text(releasedQuantity);
        $('#history_assigned_to').text(valueOrDash(item.assigned_to));
        $('#history_department').text(valueOrDash(item.department));
        $('#history_location').text(valueOrDash(item.location));
        $('#history_status').text(valueOrDash(item.status));
        $('#history_remarks').text(valueOrDash(item.remarks));

        new bootstrap.Modal(document.getElementById('inventoryItemHistoryModal')).show();
    });

    $('#releaseInventoryItemForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);

        submitAjaxForm({
            form: $form,
            button: $('#releaseInventoryItemSubmitButton'),
            url: $form.data('release-url'),
            method: 'POST',
            data: $form.serialize(),
            loadingText: 'Releasing...',
            onSuccess: function (response) {
                bootstrap.Modal.getInstance(document.getElementById('releaseInventoryItemModal')).hide();
                updateCurrentInventoryItem(response.item);
                refreshInventoryItemsTable();
                refreshInventoryReleasesTable();
                resetReleaseInventoryItemModal();
            }
        });
    });

    $(document).on('click', '.view-release-record', function () {
        const release = $(this).data('release');
        const valueOrDash = function (value) {
            return value || '—';
        };

        $('#view_release_date').text(valueOrDash(release.date));
        $('#view_release_item_name').text(valueOrDash(release.item_name));
        $('#view_release_item_remarks').text(valueOrDash(release.item_remarks));
        $('#view_release_quantity').text(valueOrDash(release.quantity));
        $('#view_release_department').text(valueOrDash(release.department));
        $('#view_release_location').text(valueOrDash(release.location));
        $('#view_release_purpose').text(valueOrDash(release.purpose));
        $('#view_release_remarks').text(valueOrDash(release.remarks));
        $('#view_release_released_to').text(valueOrDash(release.released_to));
        $('#view_release_released_by').text(valueOrDash(release.released_by));

        new bootstrap.Modal(document.getElementById('viewReleaseRecordModal')).show();
    });

    $(document).on('click', '.edit-release-record', function () {
        resetEditReleaseRecordModal();

        const release = $(this).data('release');
        const updateUrl = $(this).data('update-url');
        const $form = $('#editReleaseRecordForm');

        $('#editReleaseRecordItemNote').text(release.item_name + ' was released by ' + release.released_by + ' on ' + release.date + '.');
        $('#edit_release_quantity').val(release.quantity);
        setDepartmentAndSection('#edit_release_department', '#edit_release_location', release.department, release.location);
        $('#edit_release_purpose').val(release.purpose);
        $('#edit_release_remarks').val(release.remarks);
        $form.data('update-url', updateUrl);

        new bootstrap.Modal(document.getElementById('editReleaseRecordModal')).show();
    });

    $('#editReleaseRecordForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);

        submitAjaxForm({
            form: $form,
            button: $('#editReleaseRecordSubmitButton'),
            url: $form.data('update-url'),
            method: 'PUT',
            data: $form.serialize(),
            loadingText: 'Saving...',
            onSuccess: function () {
                bootstrap.Modal.getInstance(document.getElementById('editReleaseRecordModal')).hide();
                refreshInventoryItemsTable();
                refreshInventoryReleasesTable();
                resetEditReleaseRecordModal();
            }
        });
    });

    $(document).on('click', '.delete-release-record', function () {
        const itemName = $(this).data('item-name') || 'this release record';
        const quantity = $(this).data('quantity') || 0;
        const $form = $('#deleteReleaseRecordForm');

        clearValidation($form);
        $('#delete_release_record_url').val($(this).data('delete-url'));
        $('#delete_release_record_item_name').text(itemName + ' (' + quantity + ' item(s))');

        new bootstrap.Modal(document.getElementById('deleteReleaseRecordModal')).show();
    });

    $('#deleteReleaseRecordForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const $button = $('#deleteReleaseRecordSubmitButton');
        const buttonText = $button.text();

        clearValidation($form);
        $button.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: $('#delete_release_record_url').val(),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (response) {
                notify('success', response.success || 'Release record deleted successfully.');
                bootstrap.Modal.getInstance(document.getElementById('deleteReleaseRecordModal')).hide();
                refreshInventoryItemsTable();
                refreshInventoryReleasesTable();
                $form[0].reset();
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Unable to delete release record.';
                $('#deleteReleaseRecordFormAlert').removeClass('d-none').text(message);
                notify('error', message);
            },
            complete: function () {
                $button.prop('disabled', false).text(buttonText);
            }
        });
    });

    $(document).on('click', '.delete-inventory-item', function () {
        const deleteUrl = $(this).data('delete-url');
        const itemName = $(this).data('item-name') || 'this inventory item';
        const $form = $('#deleteInventoryItemForm');

        clearValidation($form);
        $('#delete_inventory_item_url').val(deleteUrl);
        $('#delete_inventory_item_name').text(itemName);

        new bootstrap.Modal(document.getElementById('deleteInventoryItemModal')).show();
    });

    $('#deleteInventoryItemForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const $button = $('#deleteInventoryItemSubmitButton');
        const buttonText = $button.text();

        clearValidation($form);
        $button.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: $('#delete_inventory_item_url').val(),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (response) {
                notify('success', response.success || 'Inventory item deleted successfully.');
                bootstrap.Modal.getInstance(document.getElementById('deleteInventoryItemModal')).hide();
                refreshInventoryItemsTable();
                refreshInventoryReleasesTable();
                $form[0].reset();
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Unable to delete inventory item.';
                $('#deleteInventoryItemFormAlert').removeClass('d-none').text(message);
                notify('error', message);
            },
            complete: function () {
                $button.prop('disabled', false).text(buttonText);
            }
        });
    });

    $('#inventoryItemForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const isEdit = $form.find('input[name="_method"]').val() === 'PUT';
        const url = isEdit ? $form.data('update-url') : $form.data('create-url');

        submitAjaxForm({
            form: $form,
            button: $('#inventoryItemSubmitButton'),
            url: url,
            method: 'POST',
            data: $form.serialize(),
            onSuccess: function (response) {
                if (!isEdit) {
                    advanceNextItemCode(response.item?.item_code);
                }

                bootstrap.Modal.getInstance(document.getElementById('inventoryItemModal')).hide();
                refreshInventoryItemsTable();
                resetInventoryItemModal();
            }
        });
    });

    $('#addInventoryPartButton, #addInventoryPartButtonInline').on('click', function () {
        resetInventoryPartModal();
    });

    $(document).on('click', '.edit-inventory-part', function () {
        resetInventoryPartModal();

        const part = $(this).data('part');
        const updateUrl = $(this).data('update-url');
        const $form = $('#inventoryPartForm');

        $('#part_id').val(part.id);
        $('#part_name').val(part.part_name);
        $('#serial_number').val(part.serial_number);
        $('#part_brand').val(part.brand);
        $('#part_model').val(part.model);
        $('#part_remarks').val(part.remarks);
        $('#part_status').val(part.status);
        $('#inventoryPartModalTitle').text('Edit Component');
        $('#part_status').val(part.status).prop('disabled', true);
        $('#replacement_reason').prop('disabled', true).val('');
        $form.data('update-url', updateUrl);
        $form.find('input[name="_method"]').val('PUT');

        new bootstrap.Modal(document.getElementById('inventoryPartModal')).show();
    });

    $('#inventoryPartForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const isEdit = $form.find('input[name="_method"]').val() === 'PUT';
        const url = isEdit ? $form.data('update-url') : $form.data('create-url');

        submitAjaxForm({
            form: $form,
            button: $('#inventoryPartSubmitButton'),
            url: url,
            method: 'POST',
            data: $form.serialize(),
            onSuccess: function () {
                bootstrap.Modal.getInstance(document.getElementById('inventoryPartModal')).hide();
                refreshItemDetails();
                resetInventoryPartModal();
            }
        });
    });

    $(document).on('click', '.mark-damaged-part', function () {
        const damageUrl = $(this).data('damage-url');
        const partName = $(this).data('part-name');
        const $form = $('#damagePartForm');

        $form[0].reset();
        clearValidation($form);
        $('#damage_url').val(damageUrl);
        $('#damagePartModal .modal-title').text('Mark ' + partName + ' as Damaged');

        new bootstrap.Modal(document.getElementById('damagePartModal')).show();
    });

    $('#damagePartForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        submitAjaxForm({
            form: $form,
            button: $('#damagePartSubmitButton'),
            url: $('#damage_url').val(),
            method: 'POST',
            data: $form.serialize(),
            onSuccess: function () {
                bootstrap.Modal.getInstance(document.getElementById('damagePartModal')).hide();
                refreshItemDetails();
                $form[0].reset();
            }
        });
    });

    $(document).on('click', '.replace-inventory-part', function () {
        const part = $(this).data('part');
        const replaceUrl = $(this).data('replace-url');
        const $form = $('#replacePartForm');

        $form[0].reset();
        clearValidation($form);

        $('#replace_url').val(replaceUrl);
        $('#replace_part_name').val(part.part_name);
        $('#replace_brand').val(part.brand);
        $('#replace_model').val(part.model);
        $('#replacePartModal .modal-title').text('Replace ' + part.part_name);

        new bootstrap.Modal(document.getElementById('replacePartModal')).show();
    });

    $('#replacePartForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        submitAjaxForm({
            form: $form,
            button: $('#replacePartSubmitButton'),
            url: $('#replace_url').val(),
            method: 'POST',
            data: $form.serialize(),
            onSuccess: function () {
                bootstrap.Modal.getInstance(document.getElementById('replacePartModal')).hide();
                refreshItemDetails($('#replace_part_name').val());
                $form[0].reset();
            }
        });
    });

    $(document).on('click', '.delete-inventory-part', function () {
        const deleteUrl = $(this).data('delete-url');
        const partName = $(this).data('part-name') || 'this component';
        const $form = $('#deletePartForm');

        clearValidation($form);
        $('#delete_part_url').val(deleteUrl);
        $('#delete_part_name').text(partName);

        new bootstrap.Modal(document.getElementById('deletePartModal')).show();
    });

    $('#deletePartForm').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const $button = $('#deletePartSubmitButton');
        const buttonText = $button.text();

        clearValidation($form);
        $button.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: $('#delete_part_url').val(),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (response) {
                notify('success', response.success || 'Component deleted successfully.');
                bootstrap.Modal.getInstance(document.getElementById('deletePartModal')).hide();
                refreshItemDetails();
                $form[0].reset();
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Unable to delete component.';
                $('#deletePartFormAlert').removeClass('d-none').text(message);
                notify('error', message);
            },
            complete: function () {
                $button.prop('disabled', false).text(buttonText);
            }
        });
    });

    $(document).on('click', '.view-part-history', function () {
        const partName = $(this).data('part-name');
        refreshItemDetails(partName);
        const historyTab = document.getElementById('inventoryHistoryTab');
        if (historyTab && window.bootstrap?.Tab) {
            bootstrap.Tab.getOrCreateInstance(historyTab).show();
        }
        $('html, body').animate({
            scrollTop: $('#inventoryHistoryPane').offset().top - 120
        }, 300);
    });

    $('#clearPartHistoryFilter').on('click', function () {
        refreshItemDetails();
    });

    $(document).on('shown.bs.tab', '[data-bs-toggle="tab"]', adjustVisibleTables);

    if ($('#inventoryItemsTable').length) {
        reinitializeTable('inventoryItemsTable', []);
    }

    if ($('#inventoryPartsTable').length) {
        reinitializeTable('inventoryPartsTable', [[0, 'asc']]);
    }

    if ($('#inventoryHistoryTable').length) {
        reinitializeTable('inventoryHistoryTable', [[0, 'desc']]);
    }

    if ($('#inventoryReleasesTable').length) {
        reinitializeTable('inventoryReleasesTable', [[0, 'desc']]);
    }

    initializeTooltips();
});
