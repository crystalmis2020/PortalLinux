<?php

namespace App\Services;

use Symfony\Component\Process\Process;

// *** MUST TRY HTTP heartbeat *** //
// *** Swap or extend strategies later (e.g., HTTP heartbeat, SNMP) without touching your controllers.t *** //

class NetworkProbe
{
    /**
     * Try multiple simple strategies to determine if a host is online.
     * @param string $ip
     * @param string $strategy 'auto'|'ping'|'tcp'
     */
    public function isOnline(string $ip, string $strategy = 'auto'): bool
    {
        return match ($strategy) {
            'ping' => $this->ping($ip),
            'tcp'  => $this->tcp($ip),
            default => $this->ping($ip) ?: $this->tcp($ip), // auto (fallback to TCP if ping blocked)
        };
    }

    /** ICMP ping via OS ping command (Linux/Windows). */
    public function ping(string $ip, int $timeoutSeconds = 1): bool
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        $cmd = $isWindows
            // -n 1 => 1 echo, -w timeout(ms)
            ? ["cmd", "/C", "ping -n 1 -w " . ($timeoutSeconds * 1000) . " {$ip} >NUL"]
            // -c 1 => 1 echo, -W timeout(s) (Linux), macOS uses -W in ms? safest: use -t 1 with grep
            : ["bash", "-lc", "ping -c 1 -W {$timeoutSeconds} {$ip} >/dev/null 2>&1"];

        $process = new Process($cmd);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Quick TCP port scan to common ports (works even if ICMP blocked).
     * Returns true on the first successful connection.
     */
    public function tcp(string $ip, array $ports = [80, 443, 22, 445, 3389, 3306], float $timeout = 1.0): bool
    {
        foreach ($ports as $port) {
            $conn = @fsockopen($ip, $port, $errno, $errstr, $timeout);
            if (is_resource($conn)) {
                fclose($conn);
                return true;
            }
        }
        return false;
    }
}
