<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class InternetAccessConnectorCertificateTest extends TestCase
{
    private const ROOT_SHA1 = 'B00395EF8FDDB938715CE9BDA6674C11189BEA3A';

    private const ROOT_SHA256 = '20FEC5EE304B6E1930EE8AA7D370F6E544C3B7871CF6662645AA9C0704C87509';

    public function test_packaged_root_certificate_matches_the_installer_pins(): void
    {
        $certificatePath = __DIR__.'/../../tools/internet-access-connector/certs/support-portal-root-ca.cer';
        $der = file_get_contents($certificatePath);

        $this->assertIsString($der);
        $this->assertSame(self::ROOT_SHA1, strtoupper(sha1($der)));
        $this->assertSame(self::ROOT_SHA256, strtoupper(hash('sha256', $der)));

        $installer = file_get_contents(__DIR__.'/../../tools/internet-access-connector/install.ps1');
        $uninstaller = file_get_contents(__DIR__.'/../../tools/internet-access-connector/uninstall.ps1');

        $this->assertIsString($installer);
        $this->assertIsString($uninstaller);
        $this->assertStringContainsString(self::ROOT_SHA1, $installer);
        $this->assertStringContainsString(self::ROOT_SHA1, $uninstaller);
    }
}
