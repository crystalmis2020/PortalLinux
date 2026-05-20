$(document).ready(function () {
    let deleteUserModalInstance = null;
    const replaceAdminRouteParam = (url, value) => url.replace(ADMIN_ROUTE_PLACEHOLDER, value);
    const showAdminNotification = (type, message) => {
        const alertType = type === 'success' ? 'success' : 'danger';

        $('.admin-toast-feedback').remove();
        $('<div>')
            .addClass(`alert alert-${alertType} admin-toast-feedback shadow`)
            .attr('role', 'alert')
            .css({
                position: 'fixed',
                top: '76px',
                right: '24px',
                zIndex: 2000,
                maxWidth: '360px',
            })
            .text(message)
            .appendTo('body');

        window.setTimeout(function () {
            $('.admin-toast-feedback').fadeOut(200, function () {
                $(this).remove();
            });
        }, 5000);
    };

    const showFormFeedback = ($form, type, message) => {
        const alertType = type === 'success' ? 'success' : 'danger';
        const $modalBody = $form.find('.modal-body').first();

        $form.find('.admin-form-feedback').remove();
        $('<div>')
            .addClass(`alert alert-${alertType} admin-form-feedback`)
            .attr('role', 'alert')
            .text(message)
            .prependTo($modalBody);

        showAdminNotification(type, message);
    };

    const passwordCaseMessage = (response) => {
        if (response?.case_check?.lowercase_saved) {
            return 'Warning: password was saved as the lowercase version. Please try updating again.';
        }

        if (response?.case_check?.exact_saved) {
            return `${response.success || 'Password updated successfully.'} Uppercase/lowercase was preserved.`;
        }

        return response.success || 'Password updated successfully.';
    };

    $('#department').change(function () {
        var departmentId = $(this).val();
        if (departmentId) {
            $.ajax({
                url: ADMIN_GET_SECTIONS_URL + "/" + departmentId,
                type: "GET",
                success: function (data) {
                    $('#section').empty();
                    $.each(data, function (key, section) {
                        $('#section').append('<option value="' + section.id + '">' + section.name + '</option>');
                    });
                    // Select the first section by default
                    $('#section option:first').prop('selected', true);
                }
            });
        } else {
            $('#section').empty();
            $('#section').append('<option value="">Select Section</option>');
        }
    });

    // Form Submission with Validation and Append
    $('#addUserModal form').submit(function (e) {
        e.preventDefault();

        $.ajax({
            url: ADMIN_SAVE_USER_URL,
            type: "POST",
            data: $(this).serialize(),
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                // Append the new user to the table
                $('.user-table tbody').append(`
                    <tr>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="${replaceAdminRouteParam(ADMIN_EDIT_USER_URL, response.user.id)}" class="btn btn-primary btn-sm">View</a>
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm delete-user-button"
                                    data-user-id="${response.user.id}"
                                    data-user-name="${response.user.full_name}">
                                    Delete
                                </button>
                            </div>
                        </td>
                        <td>${response.user.full_name}</td>
                        <td>${response.user.department}</td>
                        <td>${response.user.section}</td>
                    </tr>
                `);

                $('#addUserModal').modal('hide'); // Close the modal
                $('#addUserModal form')[0].reset(); // Reset the form
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

    $(document).on('submit', '#editUserForm', function (e) {

        e.preventDefault();
        var userId = $('#user_id').val();

        $.ajax({
            url: replaceAdminRouteParam(ADMIN_UPDATE_USER_URL, userId),
            type: "POST",
            data: $(this).serialize(),
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                $('#updateProfileModal').modal("hide");

            },
            error: function (xhr) {
                let response = xhr.responseJSON;

                if (response && response.errors) {
                    $.each(response.errors, function (key, messages) {
                        showFormFeedback($('#editUserForm'), 'error', messages[0]);
                        Lobibox.notify('error', {
                            size: 'mini',
                            sound: false,
                            delay: 5000,
                            msg: messages[0]
                        });
                    });
                } else if (response && response.error) {
                    showFormFeedback($('#editUserForm'), 'error', response.error);
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: response.error
                    });
                } else {
                    showFormFeedback($('#editUserForm'), 'error', 'An unknown error occurred. Please try again.');
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: 'An unknown error occurred. Please try again.'
                    });
                }
            }
        });
    });

    $(document).on('submit', '#updatePasswordForm', function (e) {

        e.preventDefault();
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();
        var userId = $('#user_id').val();
        var formData = form.serialize();

        submitButton.prop('disabled', true).text('Updating...');

        $.ajax({
            url: replaceAdminRouteParam(ADMIN_UPDATE_PASSWORD_URL, userId),
            type: "POST",
            data: formData,
            success: function (response) {
                showFormFeedback(form, 'success', passwordCaseMessage(response));
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                $('#updatePasswordForm')[0].reset();
                $('#updatePasswordModal').modal("hide");
            },
            error: function (xhr) {
                let response = xhr.responseJSON;

                if (response && response.errors) {
                    // If Laravel validation fails, it returns an `errors` object.
                    $.each(response.errors, function (key, messages) {
                        showFormFeedback(form, 'error', messages[0]);
                        Lobibox.notify('error', {
                            size: 'mini',
                            sound: false,
                            delay: 5000,
                            msg: messages[0] // Show the first error message for each field
                        });
                    });
                } else if (response && response.error) {
                    // Generic error message
                    showFormFeedback(form, 'error', response.error);
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: response.error
                    });
                } else {
                    // If no valid response, show a default message
                    showFormFeedback(form, 'error', 'An unknown error occurred. Please try again.');
                    Lobibox.notify('error', {
                        size: 'mini',
                        sound: false,
                        delay: 5000,
                        msg: 'An unknown error occurred. Please try again.'
                    });
                }
            },
            complete: function () {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $(document).on('click', '.delete-user-button', function () {
        $('#delete_user_id').val($(this).data('user-id'));
        $('#delete_user_name').text($(this).data('user-name'));

        deleteUserModalInstance = deleteUserModalInstance || new bootstrap.Modal(document.getElementById('deleteUserModal'));
        deleteUserModalInstance.show();
    });

    $(document).on('submit', '#deleteUserForm', function (e) {
        e.preventDefault();

        var userId = $('#delete_user_id').val();
        var $button = $('#deleteUserSubmitButton');
        var originalText = $button.text();

        $button.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: replaceAdminRouteParam(ADMIN_DELETE_USER_URL, userId),
            type: "DELETE",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                Lobibox.notify('success', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: response.success
                });

                $('.delete-user-button[data-user-id="' + userId + '"]').closest('tr').remove();

                if (deleteUserModalInstance) {
                    deleteUserModalInstance.hide();
                }
            },
            error: function (xhr) {
                let response = xhr.responseJSON;
                Lobibox.notify('error', {
                    size: 'mini',
                    sound: false,
                    delay: 5000,
                    msg: (response && response.error) ? response.error : 'Failed to delete user.'
                });
            },
            complete: function () {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

});
