// public/assets/js/leave-monitoring.js
(function ($) {
    'use strict';

    $(function () {
        var $from = $('input[name="from_date"]');
        var $to = $('input[name="to_date"]');

        // --- Helpers (same as before) ---
        function ensureFeedback($el) {
            var id = $el.attr('name') + '_feedback';
            if ($el.next('.invalid-feedback').length === 0) {
                $('<div/>', { class: 'invalid-feedback', id: id }).insertAfter($el);
            }
            return $('#' + id);
        }
        function setError($el, msg) {
            $el.addClass('is-invalid');
            ensureFeedback($el).text(msg).show();
        }
        function clearError($el) {
            $el.removeClass('is-invalid');
            ensureFeedback($el).hide().text('');
        }
        function parseDate(v) {
            if (!v) return null;
            var p = v.split('-'); if (p.length !== 3) return null;
            var y = +p[0], m = +p[1] - 1, d = +p[2];
            var dt = new Date(Date.UTC(y, m, d));
            return (dt.getUTCFullYear() === y && dt.getUTCMonth() === m && dt.getUTCDate() === d) ? dt : null;
        }
        function cmpDates(a, b) {
            if (!a || !b) return 0;
            var ax = a.getTime(), bx = b.getTime();
            return ax < bx ? -1 : ax > bx ? 1 : 0;
        }

        // --- This is what "grays out" not-clickable dates ---
        // Setting min/max on the opposite field makes those dates unselectable and grayed in native pickers.
        function syncConstraints() {
            var fromVal = $from.val();
            var toVal = $to.val();

            // To Date cannot be before From Date → gray out earlier days
            if (fromVal) $to.attr('min', fromVal); else $to.removeAttr('min');

            // From Date cannot be after To Date → gray out later days
            if (toVal) $from.attr('max', toVal); else $from.removeAttr('max');
        }

        function validatePair(triggeredBy) {
            var fromDt = parseDate($from.val());
            var toDt = parseDate($to.val());

            clearError($from); clearError($to);

            if (fromDt && toDt) {
                var cmp = cmpDates(fromDt, toDt);
                if (cmp === 1) {
                    if (triggeredBy === 'from') {
                        setError($from, 'From Date cannot be later than To Date.');
                    } else {
                        setError($to, 'To Date cannot be earlier than From Date.');
                    }
                    return false;
                }
            }

            syncConstraints();
            return true;
        }

        // Initialize on load (handles pre-filled values/old())
        syncConstraints();

        // Live updates (this instantly re-renders grayed-out days in the picker)
        $from.on('change input', function () { syncConstraints(); validatePair('from'); });
        $to.on('change input', function () { syncConstraints(); validatePair('to'); });

        // Optional: block submit if invalid
        $('#leave-form').on('submit', function (e) {
            if (!validatePair('submit')) e.preventDefault();
        });
    });


})(jQuery);
