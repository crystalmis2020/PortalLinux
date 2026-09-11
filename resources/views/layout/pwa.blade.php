<meta name="theme-color" content="#02681e">
<meta name="application-name" content="CSCI Support Portal">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/images/pwa/icon-192.png') }}?v=2">
<script>
    (function () {
        let deferredInstallPrompt = null;

        function isStandalone() {
            return window.matchMedia('(display-mode: standalone)').matches
                || window.matchMedia('(display-mode: window-controls-overlay)').matches
                || window.navigator.standalone === true;
        }

        function installButtons() {
            return Array.from(document.querySelectorAll('[data-pwa-install]'));
        }

        function setInstallButtonsVisible(visible) {
            installButtons().forEach(function (button) {
                button.classList.toggle('d-none', !visible);
                button.disabled = !visible;
                button.setAttribute('aria-hidden', visible ? 'false' : 'true');
            });
        }

        async function installPortal() {
            if (!deferredInstallPrompt) {
                setInstallButtonsVisible(false);
                return false;
            }

            const prompt = deferredInstallPrompt;
            deferredInstallPrompt = null;
            setInstallButtonsVisible(false);
            await prompt.prompt();
            const choice = await prompt.userChoice;

            return choice.outcome === 'accepted';
        }

        window.SupportPortalPwa = {
            install: installPortal,
            isStandalone: isStandalone,
        };

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    setInstallButtonsVisible(!isStandalone());
                }, { once: true });
            } else {
                setInstallButtonsVisible(!isStandalone());
            }
        });

        window.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            setInstallButtonsVisible(false);
        });

        document.addEventListener('DOMContentLoaded', function () {
            setInstallButtonsVisible(Boolean(deferredInstallPrompt) && !isStandalone());
            installButtons().forEach(function (button) {
                button.addEventListener('click', installPortal);
            });
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register(
                    @json(asset('service-worker.js')),
                    { scope: @json(rtrim(request()->getBaseUrl(), '/').'/') }
                ).catch(function (error) {
                    console.warn('Support Portal service worker registration failed.', error);
                });
            });
        }
    })();
</script>
