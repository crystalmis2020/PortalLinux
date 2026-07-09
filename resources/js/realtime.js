function showRealtimeToast(type, message) {
    if (!window.Lobibox) {
        return;
    }

    window.Lobibox.notify(type, {
        size: 'mini',
        rounded: true,
        sound: false,
        delay: 5000,
        position: 'top right',
        msg: message,
    });
}

function canUseDesktopNotifications() {
    return 'Notification' in window && window.isSecureContext;
}

function openDesktopNotification(title, options = {}) {
    if (!canUseDesktopNotifications() || Notification.permission !== 'granted') {
        return;
    }

    if (document.visibilityState === 'visible' && document.hasFocus()) {
        return;
    }

    const {
        onClick = null,
        ...notificationOptions
    } = options;

    let notification;

    try {
        notification = new Notification(title, notificationOptions);
    } catch (error) {
        console.error('Desktop notification failed to open.', error);
        showRealtimeToast('warning', 'Desktop alert could not be shown in this browser window.');
        return;
    }

    notification.onclick = () => {
        window.focus();

        if (typeof onClick === 'function') {
            onClick();
        }

        notification.close();
    };

    notification.onerror = (error) => {
        console.error('Desktop notification runtime error.', error);
    };
}

function normalizePortalUrl(url) {
    const config = window.portalRealtimeConfig || {};
    const fallbackUrl = config.dashboardUrl || window.location.href;
    let target;

    try {
        target = new URL(url || fallbackUrl, window.location.href);
    } catch (error) {
        target = new URL(fallbackUrl, window.location.href);
    }

    const portalBasePath = String(config.portalBasePath || '').replace(/\/+$/, '');
    let pathname = target.pathname || '/';

    if (portalBasePath && pathname !== portalBasePath && !pathname.startsWith(`${portalBasePath}/`)) {
        pathname = `${portalBasePath}${pathname.startsWith('/') ? pathname : `/${pathname}`}`;
    }

    return `${window.location.origin}${pathname}${target.search}${target.hash}`;
}

async function requestDesktopNotificationPermission({ announce = true } = {}) {
    if (!('Notification' in window)) {
        if (announce) {
            showRealtimeToast('warning', 'This browser does not support desktop notifications.');
        }

        return 'unsupported';
    }

    if (!window.isSecureContext) {
        if (announce) {
            showRealtimeToast('warning', 'Desktop notifications need HTTPS or localhost. The current portal URL is not secure.');
        }

        return 'insecure';
    }

    if (Notification.permission === 'granted') {
        if (announce) {
            showRealtimeToast('success', 'Desktop notifications are already enabled.');
        }

        return 'granted';
    }

    if (Notification.permission === 'denied') {
        if (announce) {
            showRealtimeToast('warning', 'Desktop notifications are blocked in this browser. Please allow them in site settings.');
        }

        return 'denied';
    }

    const result = await Notification.requestPermission().catch(() => 'default');

    if (announce) {
        if (result === 'granted') {
            showRealtimeToast('success', 'Desktop notifications enabled. Minimize the portal and try again.');
        } else {
            showRealtimeToast('warning', 'Desktop notifications were not enabled.');
        }
    }

    return result;
}

function updateEnableAlertsButton(button) {
    if (!button) {
        return;
    }

    if (!('Notification' in window)) {
        button.textContent = 'Alerts Unsupported';
        button.disabled = true;
        return;
    }

    if (!window.isSecureContext) {
        button.remove();
        return;
    }

    if (Notification.permission === 'granted') {
        button.textContent = 'Desktop Alerts On';
        button.disabled = true;
        button.classList.add('btn-success');
        button.classList.remove('btn-warning');
        return;
    }

    button.textContent = 'Enable Desktop Alerts';
    button.disabled = false;
    button.classList.add('btn-warning');
}

function mountEnableAlertsButton() {
    if (!window.isSecureContext) {
        return;
    }

    const existingButton = document.getElementById('portalEnableDesktopAlerts');
    if (existingButton) {
        updateEnableAlertsButton(existingButton);
        return;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.id = 'portalEnableDesktopAlerts';
    button.className = 'btn btn-warning btn-sm';
    button.style.position = 'fixed';
    button.style.left = '16px';
    button.style.bottom = '16px';
    button.style.zIndex = '1085';
    button.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.18)';
    button.style.borderRadius = '999px';
    button.style.padding = '10px 14px';
    button.style.fontWeight = '600';
    button.style.display = 'flex';
    button.style.alignItems = 'center';
    button.style.gap = '8px';
    button.innerHTML = "<i class='bx bx-bell'></i><span>Enable Desktop Alerts</span>";
    button.addEventListener('click', async () => {
        await requestDesktopNotificationPermission();
        updateEnableAlertsButton(button);
    });

    document.body.appendChild(button);
    updateEnableAlertsButton(button);
}

function queueDesktopPermissionRequest() {
    if (!canUseDesktopNotifications() || Notification.permission !== 'default') {
        return;
    }

    const requestPermission = () => {
        requestDesktopNotificationPermission({ announce: false }).then(() => {
            updateEnableAlertsButton(document.getElementById('portalEnableDesktopAlerts'));
        });
    };

    window.addEventListener('click', requestPermission, { once: true });
    window.addEventListener('keydown', requestPermission, { once: true });
    document.addEventListener('visibilitychange', function handleVisibilityChange() {
        if (document.visibilityState === 'visible') {
            requestPermission();
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        }
    });
}

function dispatchWindowEvent(name, detail) {
    window.dispatchEvent(new CustomEvent(name, { detail }));
}

function initRealtime() {
    const config = window.portalRealtimeConfig;

    if (!window.Echo || !config?.userId) {
        return;
    }

    mountEnableAlertsButton();
    queueDesktopPermissionRequest();

    window.Echo.private(`App.Models.User.${config.userId}`)
        .listen('.portal.notification.created', (payload) => {
            dispatchWindowEvent('portal-notification-received', payload);

            const detailsUrl = normalizePortalUrl(payload?.notification?.details_url || config.dashboardUrl || window.location.href);

            openDesktopNotification(payload?.notification?.title || config.appName, {
                body: payload?.notification?.message || 'You have a new notification.',
                tag: `portal-notification-${payload?.notification?.id || Date.now()}`,
                icon: '/assets/images/favicon-32x32.png',
                badge: '/assets/images/favicon-32x32.png',
                renotify: true,
                requireInteraction: true,
                data: { url: detailsUrl },
                onClick: () => {
                    window.location.href = detailsUrl;
                },
            });
        })
        .listen('.messenger.message.created', (payload) => {
            dispatchWindowEvent('portal-messenger-message-received', payload);

            const senderName = payload?.sender?.full_name || 'MISsenger';
            const messageBody = payload?.message?.body
                || (payload?.message?.attachment?.original_name
                    ? `Sent an attachment: ${payload.message.attachment.original_name}`
                    : 'You have a new MISsenger message.');

            openDesktopNotification(senderName, {
                body: messageBody,
                tag: `messenger-message-${payload?.message?.id || Date.now()}`,
                icon: '/assets/images/favicon-32x32.png',
                badge: '/assets/images/favicon-32x32.png',
                renotify: true,
                requireInteraction: true,
                onClick: () => {
                    dispatchWindowEvent('portal-messenger-open-user', {
                        userId: payload?.sender?.id || null,
                    });
                },
            });
        })
        .listen('.messenger.messages.read', (payload) => {
            dispatchWindowEvent('portal-messenger-messages-read', payload);
        })
        .listen('.messenger.call.signal', (payload) => {
            dispatchWindowEvent('portal-messenger-call-signal-received', payload);

            if (payload?.signal?.signal_type !== 'offer') {
                return;
            }

            const callerName = payload?.sender?.full_name || 'MISsenger';

            openDesktopNotification(callerName, {
                body: 'Incoming audio call.',
                tag: `messenger-call-${payload?.signal?.call_id || Date.now()}`,
                icon: '/assets/images/favicon-32x32.png',
                badge: '/assets/images/favicon-32x32.png',
                renotify: true,
                requireInteraction: true,
                onClick: () => {
                    dispatchWindowEvent('portal-messenger-open-user', {
                        userId: payload?.sender?.id || null,
                    });
                },
            });
        });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRealtime, { once: true });
} else {
    initRealtime();
}
