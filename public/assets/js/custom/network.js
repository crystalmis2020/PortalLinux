$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on('click', '#btnAddHost', function () {
        $('#addHostModal').modal('show');
    });

    $(document).on('submit', '#addHostForm', function (e) {
        e.preventDefault();

        $.ajax({
            url: ROUTE_STORE,
            type: "POST",
            data: $(this).serialize(),
            success: function (response) {
                const row = `
                            <tr data-id="${response.host.id}">
                                <td>${response.host.ip_address}</td>
                                <td>${response.host.server_name ?? ''}</td>
                                <td>${response.host.description ?? ''}</td>
                                <td class="cat-id-${response.host.host_category_id ?? ''}">${response.category_name ?? ''}</td>
                                <td class="status"><span class="badge bg-secondary">OFFLINE</span></td>
                                <td class="last_check"></td>
                                <td>${response.added_by}</td>
                                <td>
                                <button class="btn btn-sm btn-outline-primary btnCheck">Check Now</button>
                                <button class="btn btn-sm btn-outline-warning btnEdit">Edit</button>
                                <button class="btn btn-sm btn-outline-danger btnDelete">Delete</button>
                                </td>
                            </tr>`;
                $('#hostsTable tbody').prepend(row);
                $('#addHostModal').modal('hide');
                $('#addHostForm')[0].reset();
                Lobibox.notify('success', { msg: 'Host added' });

            },
            error: function (xhr) {
                let msg = 'Error';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(a => a[0]).join('<br>');
                }
                Lobibox.notify('error', { msg });
            }
        });

    });

    $(document).on('click', '.btnCheck', function () {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const $st = tr.find('.status span');
        $st.removeClass('bg-success bg-secondary').addClass('bg-warning').text('CHECKING...');

        $.ajax({
            url: ROUTE_CHECK(id),
            type: "POST",
            success: function (response) {
                $st.removeClass('bg-warning')
                    .addClass(response.status === 'online' ? 'bg-success' : 'bg-secondary')
                    .text(response.status.toUpperCase());
                tr.find('.last_check').text(response.last_check ?? '');
                Lobibox.notify('success', { msg: 'Done Checking IP. Status updated' });
            },
            error: function (xhr) {
                $st.removeClass('bg-warning').addClass('bg-secondary').text('OFFLINE');
                Lobibox.notify('error', { msg: 'Check failed' });
            }
        });

    });


    $(document).on('click', '.btnCheck1', function () {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const $st = tr.find('.status span');
        $st.removeClass('bg-success bg-secondary').addClass('bg-warning').text('CHECKING...');
        $.post(ROUTE_CHECK(id),)
            .done(({ status, last_check }) => {
                $st.removeClass('bg-warning')
                    .addClass(status === 'online' ? 'bg-success' : 'bg-secondary')
                    .text(status.toUpperCase());
                tr.find('.last_check').text(last_check ?? '');
                Lobibox.notify('success', { msg: 'Host checked' });
            })
            .fail(() => {
                $st.removeClass('bg-warning').addClass('bg-secondary').text('OFFLINE');
                Lobibox.notify('error', { msg: 'Check failed' });
            });
    });

    // Open modal
    $(document).on('click', '.btnEdit', function () {
        var tr = $(this).closest('tr');
        var id = tr.data('id');
        var ip = $.trim(tr.find('td').eq(0).text());
        var name = $.trim(tr.find('td').eq(1).text());
        var desc = $.trim(tr.find('td').eq(2).text());
        var cat = tr.data('cat-id') || '';
        var url = tr.data('update-url');

        var form = $('#updateHostForm');
        form.data('id', id);
        form.data('update-url', url);

        form.find('[name="ip_address"]').prop('disabled', false).prop('readonly', false).val(ip);
        form.find('[name="server_name"]').val(name);
        form.find('[name="description"]').val(desc);
        form.find('[name="host_category_id"]').val(String(cat)); // preselect category

        $('#updatetHostModal').modal('show');
    });
    // Update IP
    $('#updatetHostModal form').submit(function (e) {
        e.preventDefault();

        var form = $(this);
        var url = form.data('update-url');           // ⬅️ exact URL (no placeholders)
        var host_id = form.data('id');

        // Simple validation
        var ip = form.find('[name="ip_address"]').val();
        if (!ip) {
            Lobibox.notify('error', { size: 'mini', sound: false, delay: 5000, msg: 'IP Address is required' });
            return;
        }

        $.ajax({
            url: url,
            type: "POST",                     // keep your style; spoof PUT
            data: form.serialize() + '&_method=PUT',
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini', sound: false, delay: 5000,
                    msg: 'Host updated successfully'
                });

                // Update the row inline
                var tr = $('tr[data-id="' + host_id + '"]');
                tr.find('td').eq(0).text(form.find('[name="ip_address"]').val());
                tr.find('td').eq(1).text(form.find('[name="server_name"]').val() || '');
                tr.find('td').eq(2).text(form.find('[name="description"]').val() || '');

                var $catSelect = form.find('[name="host_category_id"]');
                var catId = $catSelect.val() || '';
                var catName = catId ? $.trim($catSelect.find('option:selected').text()) : '';

                tr.find('td').eq(3).text(catName || '');

                tr.data('cat-id', catId);
                tr.attr('data-cat-id', catId);

                $('#updatetHostModal').modal('hide');
            },
            error: function (xhr) {
                let errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                if (errors) {
                    $.each(errors, function (key, value) {
                        Lobibox.notify('error', { size: 'mini', sound: false, delay: 5000, msg: value[0] });
                    });
                } else {
                    Lobibox.notify('error', {
                        size: 'mini', sound: false, delay: 5000,
                        msg: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Update failed'
                    });
                }
            }
        });
    });

    // #btnCheckAll — sequential checks with progress bar + per-row notifications + summary modal
    $('#btnCheckAll').click(function (e) {
        e.preventDefault();

        var $btn = $(this);
        var $rows = $('#hostsTable tbody tr');
        var total = $rows.length;

        if (!total) {
            Lobibox.notify('warning', { size: 'mini', sound: false, delay: 4000, msg: 'No hosts found.' });
            return;
        }

        // Progress UI
        var $wrap = $('#bulkCheckProgressWrap').removeClass('d-none');
        var $bar = $('#bulkCheckProgress').addClass('progress-bar-animated');
        var $pctTxt = $('#bulkCheckProgressText');
        var $cntTxt = $('#bulkCheckCountText');

        function setProgress(done, all) {
            var pct = Math.round((done / all) * 100) || 0;
            $bar.css('width', pct + '%').attr('aria-valuenow', pct);
            $pctTxt.text(pct + '%');
            $cntTxt.text(done + ' / ' + all);
        }

        var summary = { online: [], offline: [], error: [] };
        var index = 0, processed = 0;

        $btn.prop('disabled', true).text('Checking...');
        setProgress(0, total);

        function makeListHtml(arr) {
            if (!arr.length) return '<li class="list-group-item">None</li>';
            return arr.map(function (ip) { return '<li class="list-group-item">' + ip + '</li>'; }).join('');
        }

        function showSummary() {
            $('#sum_online_count').text(summary.online.length);
            $('#sum_offline_count').text(summary.offline.length);
            $('#sum_error_count').text(summary.error.length);

            $('#sum_online_list').html(makeListHtml(summary.online));
            $('#sum_offline_list').html(makeListHtml(summary.offline));
            $('#sum_error_list').html(makeListHtml(summary.error));

            $('#checkSummaryModal').modal('show');
        }

        function processNext() {
            if (index >= total) {
                setProgress(total, total);
                $bar.removeClass('progress-bar-animated');
                $btn.prop('disabled', false).text('Check All');

                $wrap.addClass('d-none');         // simply hide
                // $wrap.slideUp(150);            // or animate hiding

                // (optional) reset for next run
                $bar.css('width', '0%').attr('aria-valuenow', 0);
                $pctTxt.text('0%');
                $cntTxt.text('0 / 0');

                showSummary();
                return;
            }

            var $tr = $($rows.get(index));
            var url = $tr.data('check-url');                          // ✅ /network-hosts/{networkHost}/check
            var ip = $tr.data('ip') || $.trim($tr.find('td').eq(0).text());
            var $badge = $tr.find('td.status span');

            $badge.removeClass('bg-success bg-secondary bg-danger')
                .addClass('bg-warning').text('CHECKING...');

            $.ajax({
                url: url,
                type: "POST",
                data: { ip_address: ip },                                 // ✅ what check() expects

                success: function (response) {
                    var status = (response.status || 'offline').toLowerCase();
                    var last_check = response.last_check || '';

                    if (status === 'online') {
                        $badge.removeClass('bg-warning').addClass('bg-success').text('ONLINE');
                        summary.online.push(ip);
                        Lobibox.notify('success', { size: 'mini', sound: false, delay: 3000, msg: ip + ' is ONLINE' });
                    } else {
                        $badge.removeClass('bg-warning').addClass('bg-secondary').text('OFFLINE');
                        summary.offline.push(ip);
                        Lobibox.notify('error', { size: 'mini', sound: false, delay: 3000, msg: ip + ' is OFFLINE' });
                    }

                    $tr.find('td.last_check').text(last_check);
                },

                error: function () {
                    $badge.removeClass('bg-warning').addClass('bg-danger').text('ERROR');
                    summary.error.push(ip);
                    Lobibox.notify('error', { size: 'mini', sound: false, delay: 4000, msg: 'Check failed for ' + ip });
                },

                complete: function () {
                    processed++;
                    setProgress(processed, total);
                    index++;
                    processNext();
                }
            });
        }

        processNext();
    });

    // Open Delete modal
    $(document).on('click', '.btnDelete', function () {
        var tr = $(this).closest('tr');
        var id = tr.data('id');
        var ip = tr.data('ip') || $.trim(tr.find('td').eq(0).text());
        var url = tr.data('delete-url'); // DELETE /network-hosts/{networkHost}

        var form = $('#deleteHostForm');
        form.data('id', id);
        form.data('url', url);
        form[0].reset();
        $('#del_ip_label').text(ip);

        $('#deleteHostModal').modal('show');
    });

    // Submit delete (AJAX; no page refresh)
    $('#deleteHostForm').submit(function (e) {
        e.preventDefault();

        var form = $(this);
        var id = form.data('id');
        var url = form.data('url');
        var data = form.serialize() + '&_method=DELETE'; // spoof DELETE per your pattern

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini', sound: false, delay: 5000,
                    msg: response.success || 'Host deleted'
                });

                // Remove row from table
                var tr = $('#hostsTable tbody tr[data-id="' + id + '"]');
                tr.fadeOut(200, function () { $(this).remove(); });

                $('#deleteHostModal').modal('hide');
            },
            error: function (xhr) {
                let res = xhr.responseJSON || {};
                if (res.errors) {
                    $.each(res.errors, function (key, value) {
                        Lobibox.notify('error', {
                            size: 'mini', sound: false, delay: 5000,
                            msg: value[0]
                        });
                    });
                } else {
                    Lobibox.notify('error', {
                        size: 'mini', sound: false, delay: 5000,
                        msg: res.message || 'Delete failed'
                    });
                }
            }
        });
    });

    const esc = (s) => $('<div>').text(s ?? '').html();

    // Open add host category modal
    $('#btnAddHostCategory').click(function () {

        const $btn = $(this);
        const indexUrl = $btn.data('categories-index-url');
        const $form = $('#addHostCategoryForm');
        const $tbody = $('#categoryTableBody');

        // ⬅️ Reset to CREATE mode on open
        $form[0].reset();
        $form.removeData('mode').removeData('edit-id');
        $('#btnSaveCategory').text('Save Category').removeClass('btn-primary').addClass('btn-success');

        $('#addHostCategoryModal').modal('show');
        $tbody.html('<tr><td colspan="5" class="text-muted">Loading...</td></tr>');

        $.ajax({
            url: indexUrl,
            type: "GET",
            data: {},
            success: function (response) {
                var cats = response.categories || [];
                if (!cats.length) {
                    $tbody.html('<tr><td colspan="4" class="text-muted">No categories found.</td></tr>');
                    return;
                }

                var rows = cats.map(function (c) {
                    return '<tr data-cat-id="' + c.id + '">' +
                        '<td class="cat-name">' + $('<div>').text(c.name).html() + '</td>' +
                        '<td>' + $('<div>').text(c.added_by || "—").html() + '</td>' +
                        '<td>' + (c.created_at || "") + '</td>'
                        + '<td width="160" class="text-nowrap">'
                        + '<button type="button" class="btn btn-sm btn-outline-warning btnCatEdit" '
                        + 'data-cat-id="' + c.id + '" data-cat-name="' + esc(c.name) + '"><i class="fadeIn animated bx bx-pencil"></i></button> '
                        + '<button type="button" class="btn btn-sm btn-outline-danger btnCatDelete" '
                        + 'data-cat-id="' + c.id + '" data-cat-name="' + esc(c.name) + '"><i class="fadeIn animated bx bx-trash"></i></button>'
                        + '</td>'
                    '</tr>';
                }).join('');

                $tbody.html(rows);
            },
            error: function (xhr) {
                let res = xhr.responseJSON || {};
                Lobibox.notify('error', {
                    size: 'mini', sound: false, delay: 5000,
                    msg: res.message || 'Failed to load categories'
                });
                $tbody.html('<tr><td colspan="4" class="text-danger">Failed to load.</td></tr>');
            }
        });
    });


    // Submit: CREATE or UPDATE based on mode
    $('#addHostCategoryForm').submit(function (e) {
        e.preventDefault();

        const $form = $(this);
        const mode = $form.data('mode') || 'create';
        const name = $.trim($form.find('input[name="name"]').val());
        if (!name.length) {
            Lobibox.notify('error', { size: 'mini', sound: false, delay: 4000, msg: 'Category name is required.' });
            return;
        }

        if (mode === 'edit') {
            // UPDATE flow
            const id = $form.data('edit-id');
            const tpl = $('#btnAddHostCategory').data('categories-update-url'); // has ':id'
            const url = String(tpl).replace(':id', id);

            $.ajax({
                url: url,
                type: "POST",
                data: { name: name, _method: 'PUT' },
                success: function (res) {
                    Lobibox.notify('success', { size: 'mini', sound: false, delay: 4000, msg: res.success || 'Category updated.' });

                    // Update row in modal table
                    const $row = $('#categoryTableBody tr[data-cat-id="' + id + '"]');
                    $row.find('.cat-name').text(name);
                    $('.cat-id-' + id).text(name);

                    // Update all host rows on the page that use this category
                    $('tr[data-cat-id="' + id + '"] td.category').text(name);

                    // Update any <select name="host_category_id"> options
                    $('select[name="host_category_id"]').each(function () {
                        $(this).find('option[value="' + id + '"]').text(name);
                    });

                    // Keep edit/delete buttons' cached name in sync
                    $row.find('.btnCatEdit, .btnCatDelete').data('cat-name', name);

                    // ⬅️ Reset form back to CREATE mode
                    $form.removeData('mode').removeData('edit-id');
                    $form[0].reset();
                    $('#btnSaveCategory').text('Save Category').removeClass('btn-primary').addClass('btn-success');
                },
                error: function (xhr) {
                    let errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                    if (errors) {
                        $.each(errors, function (k, v) {
                            Lobibox.notify('error', { size: 'mini', sound: false, delay: 5000, msg: v[0] });
                        });
                    } else {
                        Lobibox.notify('error', {
                            size: 'mini', sound: false, delay: 5000,
                            msg: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Update failed'
                        });
                    }
                }
            });

            return; // done
        }

        // CREATE flow (unchanged)
        const storeUrl = $('#btnAddHostCategory').data('categories-store-url');

        $.ajax({
            url: storeUrl,
            type: "POST",
            data: $form.serialize(),
            success: function (response) {
                Lobibox.notify('success', { size: 'mini', sound: false, delay: 5000, msg: response.success });

                const c = response.category;
                const row = '' +
                    '<tr data-cat-id="' + c.id + '">' +
                    // '<td>' + c.id + '</td>' +
                    '<td class="cat-name">' + esc(c.name) + '</td>' +
                    '<td>' + esc(c.added_by || "—") + '</td>' +
                    '<td>' + (c.created_at || "") + '</td>' +
                    '<td width="160" class="text-nowrap">' +
                    '<button type="button" class="btn btn-sm btn-outline-warning btnCatEdit" data-cat-id="' + c.id + '" data-cat-name="' + esc(c.name) + '"><i class="fadeIn animated bx bx-pencil"></i></button> ' +
                    '<button type="button" class="btn btn-sm btn-outline-danger btnCatDelete" data-cat-id="' + c.id + '" data-cat-name="' + esc(c.name) + '"><i class="fadeIn animated bx bx-trash"></i></button>' +
                    '</td>' +
                    '</tr>';

                const $tbody = $('#categoryTableBody');
                if ($tbody.find('tr td').length === 1) $tbody.empty();
                $tbody.prepend(row);

                // Also append to any host category selects on the page
                $('select[name="host_category_id"]').each(function () {
                    if (!$(this).find('option[value="' + c.id + '"]').length) {
                        $(this).append('<option value="' + c.id + '">' + c.name + '</option>');
                    }
                });

                $form[0].reset();
            },
            error: function (xhr) {
                let errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                if (errors) {
                    $.each(errors, function (k, v) {
                        Lobibox.notify('error', { size: 'mini', sound: false, delay: 5000, msg: v[0] });
                    });
                } else {
                    Lobibox.notify('error', {
                        size: 'mini', sound: false, delay: 5000,
                        msg: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Save failed'
                    });
                }
            }
        });
    });
    // ⬅️ Click Edit: reuse the add form as an update form
    $(document).on('click', '.btnCatEdit', function () {
        const $btn = $(this);
        const id = $btn.data('cat-id');
        const name = String($btn.data('cat-name') || '');

        const $form = $('#addHostCategoryForm');
        $form.data('mode', 'edit').data('edit-id', id);
        $form.find('input[name="name"]').val(name).focus();

        // Swap button text/style
        $('#btnSaveCategory').text('Update Category').removeClass('btn-success').addClass('btn-primary');
    });


});
