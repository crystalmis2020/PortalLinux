$(document).ready(function () {
    function formatManilaDateTime(value) {
        return new Date(value).toLocaleString('en-PH', {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }

    function showReportFeedback(type, message) {
        const alertType = type === 'success' ? 'success' : 'danger';
        const actionContainer = $('.action-div').first();

        $('.report-action-feedback').remove();
        $('<div>')
            .addClass(`alert alert-${alertType} report-action-feedback w-100`)
            .attr('role', 'alert')
            .text(message)
            .prependTo(actionContainer);
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function getReportId(context) {
        const currentReportId = Number(window.CURRENT_REPORT_ID || 0);

        if (currentReportId) {
            return currentReportId;
        }

        const contextualReportId = $(context)
            .closest('form, .modal, .card, body')
            .find('input[name="report_id"], #report_id')
            .first()
            .val();

        return contextualReportId || $('#report_id').first().val();
    }

    function getActionMessage(context) {
        const contextualMessage = $(context)
            .closest('.action-div, form, .modal, body')
            .find('textarea[name="message"]:visible, #message:visible')
            .first();

        if (contextualMessage.length) {
            return contextualMessage.val() || '';
        }

        return $('#message').first().val() || '';
    }

    function formatFileSize(bytes) {
        const size = Number(bytes || 0);

        if (!size) {
            return 'Unknown size';
        }

        if (size < 1024) {
            return `${size} B`;
        }

        if (size < 1024 * 1024) {
            return `${(size / 1024).toFixed(1)} KB`;
        }

        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    function renderAttachmentPreviewFallback(message) {
        return `
            <div class="report-attachment-preview__empty">
                <div>
                    <div class="mb-3 font-22"><i class='bx bx-file'></i></div>
                    <p class="mb-2">${escapeHtml(message)}</p>
                    <p class="mb-0 small">Use Download if preview is not available for this file type.</p>
                </div>
            </div>
        `;
    }

    async function openReportAttachmentPreview({ viewUrl, downloadUrl, originalName, extension, mimeType, sizeBytes }) {
        const modalElement = document.getElementById('reportAttachmentPreviewModal');
        const previewModal = modalElement && window.bootstrap?.Modal
            ? bootstrap.Modal.getOrCreateInstance(modalElement)
            : null;

        if (!previewModal) {
            window.open(viewUrl, '_blank', 'noopener');
            return;
        }

        const title = document.getElementById('reportAttachmentPreviewTitle');
        const meta = document.getElementById('reportAttachmentPreviewMeta');
        const body = document.getElementById('reportAttachmentPreviewBody');
        const download = document.getElementById('reportAttachmentPreviewDownload');
        const lowerExtension = String(extension || '').toLowerCase();
        const lowerMimeType = String(mimeType || '').toLowerCase();
        const fileType = lowerExtension ? lowerExtension.toUpperCase() : (mimeType || 'File');

        title.textContent = originalName || 'Attachment preview';
        meta.textContent = `${fileType}${sizeBytes ? ' • ' + formatFileSize(sizeBytes) : ''}`;
        download.href = downloadUrl || '#';
        download.setAttribute('download', originalName || 'attachment');

        previewModal.show();

        if (lowerMimeType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(lowerExtension)) {
            body.innerHTML = `<img src="${escapeHtml(viewUrl)}" alt="${escapeHtml(originalName || 'Attachment')}" class="report-attachment-preview__image">`;
            return;
        }

        if (lowerMimeType === 'application/pdf' || lowerExtension === 'pdf') {
            body.innerHTML = `<iframe src="${escapeHtml(viewUrl)}" class="report-attachment-preview__frame" title="${escapeHtml(originalName || 'Attachment preview')}"></iframe>`;
            return;
        }

        if (lowerMimeType.startsWith('text/') || lowerExtension === 'txt') {
            body.innerHTML = '<div class="report-attachment-preview__empty">Loading preview...</div>';

            try {
                const response = await fetch(viewUrl, { credentials: 'same-origin' });
                if (!response.ok) {
                    throw new Error('Preview request failed.');
                }

                const text = await response.text();
                body.innerHTML = `<pre class="report-attachment-preview__text">${escapeHtml(text)}</pre>`;
            } catch (error) {
                body.innerHTML = renderAttachmentPreviewFallback('This text file could not be previewed right now.');
            }

            return;
        }

        body.innerHTML = renderAttachmentPreviewFallback('Preview is not available for this file type.');
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#assignToModal form').submit(function (e) {
        e.preventDefault();

        let assigned_users = $('.selected-user').val();

        var report_id = getReportId(this);

        if (assigned_users.length === 0) {
            Lobibox.notify('error', {
                size: 'mini',
                sound: false,
                delay: 5000,
                msg: 'No Assigned User'
            });
            return;
        }



        $.ajax({
            url: REPORT_ASSIGN_URL.replace(':id', report_id),
            type: "POST",
            data: $(this).serialize(),
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });
                location.reload(true);


            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function (key, value) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: value[0]
                    });
                });
            }
        });
    });

    $('.send-message').click(function (e) {
        let message = getActionMessage(this);
        let report_id = getReportId(this);

        //_token

        $.ajax({
            url: REPORT_MESSAGE_URL.replace(':id', report_id),
            type: "POST",
            data: { message: message },
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                // Clear the message input field
                $('.action-div textarea[name="message"], #message').val('');

                // Update the report logs section dynamically
                let logsContainer = $(".list-group-flush");
                logsContainer.empty(); // Clear existing logs before updating

                response.report_logs.forEach(log => {
                    let logHtml = `
                        <li class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${log.user.full_name}</h5>
                                <small class="text-muted">${formatManilaDateTime(log.created_at)}</small>
                            </div>
                            <span class="btn badge ${statusClasses[log.status]}" style="float:right;">
                                ${log.status.toUpperCase()}
                            </span>
                            <div class="mb-1" style="width: 80% !important;">${log.message}</div>
                            <small class="text-muted">Remarks: ${log.remarks ? log.remarks : ' '}</small>
                        </li>
                    `;
                    logsContainer.append(logHtml);
                });


            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function (key, value) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: value[0]
                    });
                });
            }
        });

    });

    $("#uploadAttachmentForm").submit(function (e) {
        e.preventDefault();

        let formData = new FormData(this);
        let report_id = getReportId(this); // Get the report ID

        $.ajax({
            url: REPORT_UPLOAD_URL.replace(':id', report_id), // URL for upload route
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                const successMessage = response.success || 'Attachment uploaded successfully.';

                // Show success notification
                Lobibox.notify("success", {
                    size: "mini",
                    sound: false,
                    delay: 5000,
                    msg: successMessage
                });
                showReportFeedback('success', successMessage);

                // Close the modal after successful upload
                $("#uploadAttachmentModal").modal("hide");
                $("#uploadAttachmentForm")[0].reset();

                // Update Attachments Section Dynamically
                let attachmentsContainer = $(".attachment-row");

                let newAttachment = `
                    <div class="report-attachment">
                        <div>
                            <div class="report-attachment__name">${escapeHtml(response.attachment.original_name)}</div>
                            <div class="report-attachment__meta">${escapeHtml(String(response.extension || 'FILE').toUpperCase())}</div>
                        </div>
                        <div class="report-attachment__actions">
                            <button
                                type="button"
                                class="report-attachment__action report-attachment-view"
                                data-attachment-view="${escapeHtml(response.view_url)}"
                                data-attachment-download="${escapeHtml(response.download_url)}"
                                data-attachment-name="${escapeHtml(response.attachment.original_name)}"
                                data-attachment-extension="${escapeHtml(response.extension || '')}"
                                data-attachment-mime="${escapeHtml(response.mime_type || '')}"
                                data-attachment-size="${escapeHtml(response.size_bytes || '')}"
                            >
                                <i class='bx bx-show'></i>
                                <span>View</span>
                            </button>
                            <a href="${escapeHtml(response.download_url)}" class="report-attachment__action">
                                <i class='bx bx-download'></i>
                                <span>Download</span>
                            </a>
                        </div>
                    </div>
                `;
                attachmentsContainer.append(newAttachment);
            },
            error: function (xhr) {
                const response = xhr.responseJSON;

                if (response && response.errors) {
                    $.each(response.errors, function (key, value) {
                        showReportFeedback('error', value[0]);
                        Lobibox.notify("error", {
                            size: "mini",
                            sound: false,
                            delay: 5000,
                            msg: value[0]
                        });
                    });
                    return;
                }

                const errorMessage = response?.error || response?.message || 'Failed to upload attachment.';
                showReportFeedback('error', errorMessage);
                Lobibox.notify("error", {
                    size: "mini",
                    sound: false,
                    delay: 5000,
                    msg: errorMessage
                });
            }
        });
    });

    $(document).on('click', '.report-attachment-view', function () {
        openReportAttachmentPreview({
            viewUrl: this.dataset.attachmentView,
            downloadUrl: this.dataset.attachmentDownload,
            originalName: this.dataset.attachmentName,
            extension: this.dataset.attachmentExtension,
            mimeType: this.dataset.attachmentMime,
            sizeBytes: this.dataset.attachmentSize
        });
    });

    $(document).on("submit", "#resolveAndReAssignToSubmitForm", function (e) {
        e.preventDefault();

        let assigned_users = $('.selected-user-reassign').val();
        let message = getActionMessage(this);
        let report_id = getReportId(this);

        if (assigned_users.length === 0) {
            Lobibox.notify('error', {
                size: 'mini',
                sound: false,
                delay: 5000,
                msg: 'No Assigned User'
            });
            return;
        }

        let formData = new FormData(this);
        formData.append("message", message); // Add message field


        $.ajax({
            url: REPORT_REASSIGN_URL.replace(':id', report_id),
            type: "POST",
            data: formData,
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                // Update the assigned users dynamically
                let assignedUsersContainer = $(".assigned-users");
                assignedUsersContainer.empty(); // Clear previous names

                if (response.assigned_users.length > 0) {
                    let assignedUserNames = response.assigned_users.map(user => user.full_name).join(', ');
                    assignedUsersContainer.append(assignedUserNames);
                } else {
                    assignedUsersContainer.append(`<span>No assigned users</span>`);
                }

                // Update the report logs section dynamically
                let logsContainer = $(".list-group-flush");
                logsContainer.empty(); // Clear existing logs before updating

                response.report_logs.forEach(log => {
                    let logHtml = `
                        <li class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${log.user.full_name}</h5>
                                <small class="text-muted">${formatManilaDateTime(log.created_at)}</small>
                            </div>
                            <span class="btn badge ${statusClasses[log.status]}" style="float:right;">
                                ${log.status.toUpperCase()}
                            </span>
                            <div class="mb-1" style="width: 80% !important;">${log.message}</div>
                            <small class="text-muted">Remarks: ${log.remarks ? log.remarks : ' '}</small>
                        </li>
                    `;
                    logsContainer.append(logHtml);
                });

                $("#resolveAndReAssignToModal").modal("hide");
                $('.action-div textarea[name="message"], #message').val('');
                $('.selected-user').val('');


            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function (key, value) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: value[0]
                    });
                });
            }
        });
    });

    $('.resolve-report').click(function (e) {
        let message = getActionMessage(this);
        let report_id = getReportId(this);


        $('.btn-resolve-report').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading..');

        $.ajax({
            url: REPORT_RESOLVE_URL.replace(':id', report_id),
            type: "POST",
            data: { message: message },
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                $(".report-status").removeClass(function (index, className) {
                    return (className.match(/\bbg-\S+/g) || []).join(' ');
                }).addClass('btn-success');

                // Update the report logs section dynamically
                let logsContainer = $(".list-group-flush");
                logsContainer.empty(); // Clear existing logs before updating

                response.report_logs.forEach(log => {
                    let logHtml = `
                        <li class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${log.user.full_name}</h5>
                                <small class="text-muted">${formatManilaDateTime(log.created_at)}</small>
                            </div>
                            <span class="btn badge ${statusClasses[log.status]}" style="float:right;">
                                ${log.status.toUpperCase()}
                            </span>
                            <div class="mb-1" style="width: 80% !important;">${log.message}</div>
                            <small class="text-muted">Remarks: ${log.remarks ? log.remarks : ' '}</small>
                        </li>
                    `;
                    logsContainer.append(logHtml);
                });
                $('.action-div').html('');
                $('.btn-resolve-report').prop('disabled', false).html('<i class="bx bx-check mr-1"></i>Resolve');

            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function (key, value) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: value[0]
                    });
                });
                $('.btn-resolve-report').prop('disabled', false).html('<i class="bx bx-check mr-1"></i>Resolve');
            }
        });

    });

    $(document).on('click', '.reopen-report', function () {
        const button = $(this);
        let reportId = getReportId(this);
        let remarks = getActionMessage(this);

        if (!remarks.trim()) {
            showReportFeedback('error', 'Please enter a message before re-opening the report.');
            Lobibox.notify('error', {
                size: 'mini',
                sound: false,
                delay: 5000,
                msg: 'Please enter a message before re-opening the report.'
            });
            return;
        }

        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading..');

        $.ajax({
            url: REPORT_REOPEN_URL.replace(':id', reportId),
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                message: remarks
            },
            success: function (response) {
                showReportFeedback('success', response.success || 'Report successfully re-opened.');
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                // ✅ Clear message field
                $('.action-div textarea[name="message"], #message').val('');

                $('.close-reopen').remove();
                $('.send-attached').show();

                // ✅ Update report logs
                let logsContainer = $(".list-group-flush");
                logsContainer.empty();

                response.report_logs.forEach(log => {
                    let logHtml = `
                        <li class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${log.user ? log.user.full_name : 'Unknown User'}</h5>
                                <small class="text-muted">${formatManilaDateTime(log.created_at)}</small>
                            </div>
                            <span class="btn badge ${statusClasses[log.status] || 'badge-default'}" style="float:right;">
                                ${log.status.toUpperCase()}
                            </span>
                            <div class="mb-1" style="width: 80% !important;">${log.message}</div>
                            <small class="text-muted">Remarks: ${log.remarks ?? 'N/A'}</small>
                        </li>
                    `;
                    logsContainer.append(logHtml);
                });

                // ✅ Update the report status badge color
                $(".report-status").each(function () {
                    $(this).removeClass(function (i, className) {
                        return (className.match(/\bbg-\S+/g) || []).join(" ");
                    }).addClass(statusClasses["in progress"]).text('IN PROGRESS');
                });
            },
            error: function (xhr) {
                const response = xhr.responseJSON;

                if (response && response.errors) {
                    $.each(response.errors, function (key, value) {
                        showReportFeedback('error', value[0]);
                        Lobibox.notify('error', {
                            size: 'mini',
                            sound: false,
                            delay: 5000,
                            msg: value[0]
                        });
                    });
                } else {
                    const message = response?.error || response?.message || 'Unable to re-open this report.';
                    showReportFeedback('error', message);
                }
            },
            complete: function () {
                button.prop('disabled', false).html('<i class="bx bx-folder-open mr-1"></i>Re-open');
            }
        });
    });

    $(document).on('click', '.close-report', function () {
        const reportId = getReportId(this);
        const remarks = getActionMessage(this);

        $('.btn-close-report').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading..');

        $.ajax({
            url: REPORT_CLOSE_URL.replace(':id', reportId),
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                message: remarks
            },
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                // ✅ Reset remarks
                $('.action-div textarea[name="message"], #message').val('').remove();
                $('.close-reopen').remove();

                // ✅ Update logs
                let logsContainer = $(".list-group-flush");
                logsContainer.empty();

                response.report_logs.forEach(log => {
                    let logHtml = `
                        <li class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${log.user ? log.user.full_name : 'Unknown User'}</h5>
                                <small class="text-muted">${formatManilaDateTime(log.created_at)}</small>
                            </div>
                            <span class="btn badge ${statusClasses[log.status] || 'badge-default'}" style="float:right;">
                                ${log.status.toUpperCase()}
                            </span>
                            <div class="mb-1" style="width: 80% !important;">${log.message}</div>
                            <small class="text-muted">Remarks: ${log.remarks ?? 'N/A'}</small>
                        </li>
                    `;
                    logsContainer.append(logHtml);
                });

                // ✅ Update status badge
                $(".report-status").each(function () {
                    $(this).removeClass(function (i, className) {
                        return (className.match(/\bbg-\S+/g) || []).join(" ");
                    }).addClass(statusClasses["closed"]).text('CLOSED');
                });
                $('.btn-close-report').prop('disabled', true).html('<i class="bx bx-check-square mr-1 "></i>Close');
            },
            error: function (xhr) {
                const response = xhr.responseJSON;

                if (response && response.errors) {
                    $.each(response.errors, function (key, value) {
                        Lobibox.notify('error', {
                            size: 'mini',
                            sound: false,
                            delay: 5000,
                            msg: value[0]
                        });
                    });
                } else {
                    showReportFeedback('error', response?.error || response?.message || 'Unable to close this report.');
                }

                $('.btn-close-report').prop('disabled', false).html('<i class="bx bx-check-square mr-1 "></i>Close');
            }
        });
    });

    $(document).on('click', '.assign-btn', function () {
        var report_id = $(this).attr('id')
        $('#report_id').val(report_id);
    });

    $('#assignToModal2 form').submit(function (e) {
        e.preventDefault();

        let assigned_users = $('.selected-user').val();

        var report_id = getReportId(this);

        if (assigned_users.length === 0) {
            Lobibox.notify('error', {
                size: 'mini',
                sound: false,
                delay: 5000,
                msg: 'No Assigned User'
            });
            return;
        }

        $.ajax({
            url: REPORT_ASSIGN_URL.replace(':id', report_id),
            type: "POST",
            data: $(this).serialize(),
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                // Update the assigned users dynamically
                let assignedUsersContainer = $(".td-" + report_id);
                assignedUsersContainer.empty(); // Clear previous names

                if (response.assigned_users.length > 0) {
                    let assignedUserNames = response.assigned_users.map(user => user.full_name).join('<br>');
                    assignedUsersContainer.append(assignedUserNames);
                } else {
                    assignedUsersContainer.append(` `);
                }

                $(".report-status-" + report_id).removeClass(function (index, className) {
                    return (className.match(/\bbg-\S+/g) || []).join(' ');
                }).addClass('btn-warning').text('IN PROGRESS');

                $("#assignToModal2").modal("hide");
                $('.selected-user-reassign').val('');


            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function (key, value) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: value[0]
                    });
                });
            }
        });
    });

});
