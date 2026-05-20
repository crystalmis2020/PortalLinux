<script>
    (function () {
        const bellCount = document.getElementById('portalNotificationCount');
        const notificationList = document.getElementById('portalNotificationList');
        const notificationSummary = document.getElementById('portalNotificationSummary');
        const selectAll = document.getElementById('portalNotificationSelectAll');
        const markReadButton = document.getElementById('portalNotificationMarkRead');
        const markUnreadButton = document.getElementById('portalNotificationMarkUnread');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const bulkUpdateUrl = @json(route('notifications.bulk-update'));

        if (!bellCount || !notificationList || !notificationSummary) {
            return;
        }

        function toText(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function setUnreadCount(count) {
            const nextCount = Number(count || 0);
            bellCount.textContent = String(nextCount);
        }

        function updateTotalCount() {
            const items = notificationList.querySelectorAll('[data-notification-id]');
            notificationSummary.textContent = `${items.length} New`;
        }

        function getNotificationCheckboxes() {
            return Array.from(notificationList.querySelectorAll('.portal-notification-checkbox'));
        }

        function getSelectedNotificationIds() {
            return getNotificationCheckboxes()
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);
        }

        function syncBulkControls() {
            const checkboxes = getNotificationCheckboxes();
            const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const hasSelection = selectedCount > 0;

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
            }

            if (markReadButton) {
                markReadButton.disabled = !hasSelection;
            }

            if (markUnreadButton) {
                markUnreadButton.disabled = !hasSelection;
            }
        }

        function buildNotificationItem(notification) {
            const link = notification.details_url || '#';
            const avatarUrl = notification.from_user?.profile_photo_url || @json(asset('assets/images/avatars/avatar-1.png'));
            const sender = notification.from_user?.full_name
                ? ` by ${toText(notification.from_user.full_name)}`
                : '';
            const reportLine = notification.report_id
                ? `<br>Report ID: ${toText(notification.report_id)}`
                : '';

            return `
                <div class="dropdown-item" data-notification-id="${toText(notification.id)}" data-notification-read="No">
                    <div class="d-flex align-items-center gap-2">
                        <input class="form-check-input portal-notification-checkbox" type="checkbox" value="${toText(notification.id)}" aria-label="Select notification ${toText(notification.id)}">
                        <a class="d-flex align-items-center flex-grow-1 text-decoration-none text-reset" href="${toText(link)}">
                            <div class="user-online">
                                <img src="${toText(avatarUrl)}" class="msg-avatar" alt="user avatar">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="msg-name">
                                    ${toText(notification.title)}
                                    <span class="msg-time float-end">${toText(notification.created_at_human || 'Just now')}</span>
                                </h6>
                                <p class="msg-info">
                                    ${toText(notification.message)}${sender}${reportLine}
                                </p>
                            </div>
                        </a>
                    </div>
                </div>
            `;
        }

        async function updateSelectedNotifications(isRead) {
            const selectedIds = getSelectedNotificationIds();

            if (!selectedIds.length || !csrfToken) {
                return;
            }

            [markReadButton, markUnreadButton].forEach((button) => {
                if (button) {
                    button.disabled = true;
                }
            });

            const response = await fetch(bulkUpdateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    notification_ids: selectedIds,
                    is_read: isRead,
                }),
            });

            if (!response.ok) {
                syncBulkControls();
                return;
            }

            const payload = await response.json();
            setUnreadCount(payload.unread_count);

            selectedIds.forEach((id) => {
                const item = Array.from(notificationList.querySelectorAll('[data-notification-id]'))
                    .find((notificationItem) => notificationItem.dataset.notificationId === id);

                if (!item) {
                    return;
                }

                item.dataset.notificationRead = isRead;
                const checkbox = item.querySelector('.portal-notification-checkbox');

                if (checkbox) {
                    checkbox.checked = false;
                }

                if (isRead === 'Yes') {
                    item.remove();
                }
            });

            updateTotalCount();
            syncBulkControls();
        }

        notificationList.addEventListener('click', function (event) {
            if (event.target.classList.contains('portal-notification-checkbox')) {
                event.stopPropagation();
                syncBulkControls();
            }
        });

        notificationList.addEventListener('change', function (event) {
            if (event.target.classList.contains('portal-notification-checkbox')) {
                syncBulkControls();
            }
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                getNotificationCheckboxes().forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });

                syncBulkControls();
            });
        }

        if (markReadButton) {
            markReadButton.addEventListener('click', function () {
                updateSelectedNotifications('Yes');
            });
        }

        if (markUnreadButton) {
            markUnreadButton.addEventListener('click', function () {
                updateSelectedNotifications('No');
            });
        }

        window.addEventListener('portal-notification-received', function (event) {
            const payload = event.detail || {};
            const notification = payload.notification;

            if (!notification) {
                return;
            }

            setUnreadCount(payload.unread_count);

            const existing = notificationList.querySelector(`[data-notification-id="${notification.id}"]`);
            if (existing) {
                existing.remove();
            }

            notificationList.insertAdjacentHTML('afterbegin', buildNotificationItem(notification));

            const items = notificationList.querySelectorAll('[data-notification-id]');
            items.forEach(function (item, index) {
                if (index >= 8) {
                    item.remove();
                }
            });

            updateTotalCount();
            syncBulkControls();

            if (window.Lobibox) {
                Lobibox.notify('info', {
                    size: 'mini',
                    rounded: true,
                    delayIndicator: true,
                    sound: false,
                    position: 'top right',
                    icon: 'bx bx-bell',
                    msg: `${notification.title}: ${notification.message}`,
                });
            }
        });

        syncBulkControls();

    })();
</script>
