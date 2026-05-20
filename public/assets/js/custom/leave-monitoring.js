$(document).on("submit", "#leave-form", function (e) {
    e.preventDefault();

    // CSRF for Laravel (once is enough anywhere in your app)
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    var formData = $(this).serialize();

    $.ajax({
        url: LEAVE_STORE_URL,
        type: "POST",
        data: formData,
        success: function (response) {
            // success toast
            Lobibox.notify("success", {
                size: "mini",
                sound: false,
                delay: 4000,
                msg: response.message || "Leave saved successfully.",
            });

            // rebuild table from returned leaves
            var rows = "";
            var today = new Date().toLocaleDateString("en-CA", {
                timeZone: "Asia/Manila",
            }); // YYYY-MM-DD

            if (response.leaves && response.leaves.length) {
                $.each(response.leaves, function (_, l) {
                    var status =
                        today >= l.from_date && today <= l.to_date
                            ? '<span class="badge bg-danger">On Leave</span>'
                            : '<span class="badge bg-warning text-dark">Upcoming</span>';

                    rows +=
                        "<tr>" +
                        "<td>" +
                        (l.user_full_name || "") +
                        "</td>" +
                        "<td>" +
                        (l.section_name || "") +
                        "</td>" +
                        "<td>" +
                        (l.from_date || "") +
                        "</td>" +
                        "<td>" +
                        (l.to_date || "") +
                        "</td>" +
                        "<td>" +
                        status +
                        "</td>" +
                        "<td>" +
                        (l.reason || "") +
                        "</td>" +
                        "<td>" +
                        (l.leave_address || "") +
                        "</td>" +
                        "</tr>";
                });
            } else {
                rows =
                    '<tr><td colspan="7" class="text-center text-muted py-4">' +
                    "No personnel currently on or scheduled for leave in your section." +
                    "</td></tr>";
            }

            $("#leave-table-body").html(rows);

            // clear only variable fields (keep employee/section)
            $("#leave-form")
                .find(
                    'input[name="from_date"], input[name="to_date"], input[name="reason"], input[name="leave_address"]',
                )
                .val("");
        },
        error: function (xhr) {
            var response = xhr.responseJSON;

            if (response && response.errors) {
                $.each(response.errors, function (key, messages) {
                    Lobibox.notify("error", {
                        size: "mini",
                        sound: false,
                        delay: 5000,
                        msg: messages[0],
                    });
                });
            } else if (response && response.error) {
                Lobibox.notify("error", {
                    size: "mini",
                    sound: false,
                    delay: 5000,
                    msg: response.error,
                });
            } else {
                Lobibox.notify("error", {
                    size: "mini",
                    sound: false,
                    delay: 5000,
                    msg: "An unknown error occurred. Please try again.",
                });
            }
        },
    });
});
