<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProgressiveWebAppTest extends TestCase
{
    public function test_manifest_opens_the_internet_access_page_inside_the_support_scope(): void
    {
        $manifestPath = __DIR__.'/../../public/manifest.webmanifest';
        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/support/', $manifest['id']);
        $this->assertSame('/support/', $manifest['scope']);
        $this->assertSame('/support/internet-access?source=pwa', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertContains('192x192', array_column($manifest['icons'], 'sizes'));
        $this->assertContains('512x512', array_column($manifest['icons'], 'sizes'));

        $icon192 = getimagesize(__DIR__.'/../../public/assets/images/pwa/icon-192.png');
        $icon512 = getimagesize(__DIR__.'/../../public/assets/images/pwa/icon-512.png');

        $this->assertSame([192, 192], [$icon192[0], $icon192[1]]);
        $this->assertSame([512, 512], [$icon512[0], $icon512[1]]);

        $generator = file_get_contents(__DIR__.'/../../tools/generate-pwa-icons.mjs');
        $this->assertStringContainsString("drawText(pixels, size, 'MIS'", $generator);
        $this->assertStringContainsString("drawText(pixels, size, 'SUPPORT'", $generator);
    }

    public function test_service_worker_never_caches_authenticated_application_routes(): void
    {
        $serviceWorker = file_get_contents(__DIR__.'/../../public/service-worker.js');

        $this->assertIsString($serviceWorker);
        $this->assertStringContainsString("request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString("fetch(request, { cache: 'no-store' })", $serviceWorker);
        $this->assertStringContainsString('`${BASE_PATH}/assets/`', $serviceWorker);
        $this->assertStringContainsString('`${BASE_PATH}/build/`', $serviceWorker);
        $this->assertStringContainsString('Never cache status, connector-token, download', $serviceWorker);
    }

    public function test_portal_views_register_the_pwa_and_pwa_connect_uses_windows_handoff(): void
    {
        $appLayout = file_get_contents(__DIR__.'/../../resources/views/layout/app.blade.php');
        $login = file_get_contents(__DIR__.'/../../resources/views/auth/login.blade.php');
        $pwa = file_get_contents(__DIR__.'/../../resources/views/layout/pwa.blade.php');
        $internetAccess = file_get_contents(__DIR__.'/../../resources/views/internet-access/index.blade.php');

        $this->assertStringContainsString("@include('layout.pwa')", $appLayout);
        $this->assertStringContainsString("@include('layout.pwa')", $login);
        $this->assertStringContainsString('id="loginPwaInstallButton"', $login);
        $this->assertStringContainsString('data-pwa-install', $login);
        $this->assertStringContainsString("navigator.serviceWorker.register", $pwa);
        $this->assertStringContainsString('data-pwa-install', $internetAccess);
        $this->assertStringContainsString('isRunningAsPwa()', $internetAccess);
        $this->assertStringContainsString('window.location.assign(launchUri)', $internetAccess);
    }
}
