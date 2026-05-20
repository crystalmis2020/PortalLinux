$(document).ready(function () {

    $('#form-submit-issue').validate({
        rules: {
            issue: "required"
        },
        messages: {
            issue: "Please say something about the issue you found.",
        },
        submitHandler: function (form) {
            form.submit();
        }
    });

    $('#assignToSubmitForm').validate({
        rules: {
            assigned_users: "required"
        },
        messages: {
            assigned_users: "Please specify person(s) to be assign",
        },
        submitHandler: function (form) {
            form.submit();
        }
    });

});
