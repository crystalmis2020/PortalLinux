<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class InternetAccessConnectorViewTest extends TestCase
{
    public function test_connect_action_uses_a_modal_with_pwa_and_background_protocol_handoffs(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/internet-access/index.blade.php');

        $this->assertIsString($view);
        $this->assertStringContainsString('<button id="connectButton"', $view);
        $this->assertStringNotContainsString('<a id="connectButton"', $view);
        $this->assertStringContainsString('id="connectorLaunchModal"', $view);
        $this->assertStringContainsString('id="connectorProtocolFrameHost"', $view);
        $this->assertStringContainsString('launchConnectorInBackground()', $view);
        $this->assertStringContainsString('window.location.assign(launchUri)', $view);
    }
}
