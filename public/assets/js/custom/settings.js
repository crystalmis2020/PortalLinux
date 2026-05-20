$(document).ready(function () {
    const showPasswordNotification = (type, message) => {
        const alertType = type === 'success' ? 'success' : 'danger';

        $('.password-toast-feedback').remove();
        $('<div>')
            .addClass(`alert alert-${alertType} password-toast-feedback shadow`)
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
            $('.password-toast-feedback').fadeOut(200, function () {
                $(this).remove();
            });
        }, 5000);
    };

    const showPasswordFeedback = (type, message) => {
        const alertType = type === 'success' ? 'success' : 'danger';
        const form = $('#passwordForm');

        form.find('.password-form-feedback').remove();
        $('<div>')
            .addClass(`alert alert-${alertType} password-form-feedback`)
            .attr('role', 'alert')
            .text(message)
            .prependTo(form);

        showPasswordNotification(type, message);
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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#passwordForm').submit(function (e) {
        e.preventDefault();

        const form = this;
        const submitButton = $(form).find('button[type="submit"]');
        const originalButtonText = submitButton.text();
        let newPassword = $('#new_password').val();
        let confirmPassword = $('#confirm_password').val();

        // Custom password validation regex
        const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d])[A-Za-z\d\S]{8,}$/;

        if (!passwordRegex.test(newPassword)) {
            showPasswordFeedback('error', 'New password must be at least 8 characters, include 1 uppercase letter, 1 number, and 1 special character.');
            Lobibox.notify('error', {
                size: 'mini',
                sound: false,
                delay: 5000,
                msg: 'New password must be at least 8 characters, include 1 uppercase letter, 1 number, and 1 special character.'
            });
            return;
        }

        if (newPassword !== confirmPassword) {
            showPasswordFeedback('error', 'Confirm password does not match new password.');
            Lobibox.notify('error', {
                size: 'mini',
                sound: false,
                delay: 5000,
                msg: 'Confirm password does not match new password.'
            });
            return;
        }

        submitButton.prop('disabled', true).text('Updating...');

        $.ajax({
            url: SETTING_PASSWORD_URL,
            type: 'POST',
            data: $(form).serialize(),
            success: function (response) {
                showPasswordFeedback('success', passwordCaseMessage(response));
                Lobibox.notify('success', { msg: response.success });
                $('#passwordForm')[0].reset();
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};

                if (response.errors) {
                    $.each(response.errors, function (key, value) {
                        showPasswordFeedback('error', value[0]);
                        Lobibox.notify('error', { msg: value[0] });
                    });
                    return;
                }

                showPasswordFeedback('error', response.message || response.error || 'Unable to update password. Please try again.');
                Lobibox.notify('error', {
                    msg: response.message || response.error || 'Unable to update password. Please try again.'
                });
            },
            complete: function () {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });


});
