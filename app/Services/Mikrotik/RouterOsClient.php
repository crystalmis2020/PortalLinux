<?php

namespace App\Services\Mikrotik;

use RuntimeException;

class RouterOsClient
{
    protected bool $connected = false;

    /** @var resource|null */
    protected $socket = null;

    protected int $port;

    protected int $timeout;

    public function __construct()
    {
        $this->port = (int) config('mikrotik.port', 8728);
        $this->timeout = (int) config('mikrotik.timeout', 10);
    }

    public function createTemporaryAccess(string $username, string $password, string $profile, string $comment): ?string
    {
        return $this->withConnection(function () use ($username, $password, $profile, $comment) {
            $response = $this->comm('/ppp/secret/add', [
                'name' => $username,
                'password' => $password,
                'service' => config('mikrotik.service', 'pppoe'),
                'profile' => $profile,
                'comment' => $comment,
            ]);

            $this->throwIfTrap($response);

            return $this->findSecretId($username);
        });
    }

    public function isUserConnected(string $username): bool
    {
        return $this->withConnection(function () use ($username) {
            $active = $this->comm('/ppp/active/print', ['?name' => $username]);
            $this->throwIfTrap($active);

            return count($active) > 0;
        });
    }

    public function removeAccess(string $username): void
    {
        $this->withConnection(function () use ($username) {
            $secretId = $this->findSecretId($username);

            if ($secretId) {
                $response = $this->comm('/ppp/secret/remove', ['.id' => $secretId]);
                $this->throwIfTrap($response);
            }
        });
    }

    public function testConnection(): bool
    {
        return $this->withConnection(fn () => true);
    }

    protected function findSecretId(string $username): ?string
    {
        $secrets = $this->comm('/ppp/secret/print', ['?name' => $username]);
        $this->throwIfTrap($secrets);

        return $secrets[0]['.id'] ?? null;
    }

    protected function withConnection(callable $callback): mixed
    {
        if (! config('mikrotik.enabled')) {
            throw new RuntimeException('MikroTik integration is disabled. Set MIKROTIK_ENABLED=true when the router is ready.');
        }

        $host = config('mikrotik.host');
        $username = config('mikrotik.username');
        $password = config('mikrotik.password');

        if (! $host || ! $username || ! $password) {
            throw new RuntimeException('MikroTik host, username, or password is missing.');
        }

        $this->connect($host, $username, $password);

        try {
            return $callback();
        } finally {
            $this->disconnect();
        }
    }

    protected function connect(string $host, string $username, string $password): void
    {
        $this->socket = @fsockopen($host, $this->port, $errorNo, $errorMessage, $this->timeout);

        if (! $this->socket) {
            throw new RuntimeException("Unable to connect to MikroTik: {$errorMessage} ({$errorNo}).");
        }

        stream_set_timeout($this->socket, $this->timeout);

        $this->write('/login');
        $response = $this->read(false);

        if (($response[0] ?? null) !== '!done') {
            throw new RuntimeException('Unexpected MikroTik login challenge response.');
        }

        $this->write('/login', false);
        $this->write('=name='.$username, false);
        $this->write('=password='.$password);

        $loginResponse = $this->read(false);

        if (($loginResponse[0] ?? null) !== '!done') {
            throw new RuntimeException('MikroTik login failed.');
        }

        $this->connected = true;
    }

    protected function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
        $this->connected = false;
    }

    protected function comm(string $command, array $arguments = []): array
    {
        $this->write($command, empty($arguments));

        $index = 0;
        $count = count($arguments);

        foreach ($arguments as $key => $value) {
            $prefix = match ($key[0]) {
                '?' => "{$key}={$value}",
                '~' => "{$key}~{$value}",
                default => "={$key}={$value}",
            };

            $this->write($prefix, ++$index === $count);
        }

        return $this->read();
    }

    protected function write(string $command, bool|int $finish = true): void
    {
        foreach (explode("\n", $command) as $line) {
            $line = trim($line);
            fwrite($this->socket, $this->encodeLength(strlen($line)).$line);
        }

        if (is_int($finish)) {
            $tag = '.tag='.$finish;
            fwrite($this->socket, $this->encodeLength(strlen($tag)).$tag.chr(0));

            return;
        }

        if ($finish) {
            fwrite($this->socket, chr(0));
        }
    }

    protected function read(bool $parse = true): array
    {
        $response = [];
        $receivedDone = false;

        while (true) {
            $byte = fread($this->socket, 1);

            if ($byte === '' || $byte === false) {
                break;
            }

            $length = $this->decodeLength(ord($byte));

            if ($length > 0) {
                $payload = '';

                while (strlen($payload) < $length) {
                    $chunk = fread($this->socket, $length - strlen($payload));

                    if ($chunk === '' || $chunk === false) {
                        break;
                    }

                    $payload .= $chunk;
                }

                $response[] = $payload;
                $receivedDone = $payload === '!done' || $receivedDone;
            }

            $status = stream_get_meta_data($this->socket);

            if ((! $this->connected && empty($status['unread_bytes'])) || ($this->connected && empty($status['unread_bytes']) && $receivedDone)) {
                break;
            }
        }

        return $parse ? $this->parseResponse($response) : $response;
    }

    protected function decodeLength(int $byte): int
    {
        if (($byte & 128) === 0) {
            return $byte;
        }

        if (($byte & 192) === 128) {
            return (($byte & 63) << 8) + ord(fread($this->socket, 1));
        }

        if (($byte & 224) === 192) {
            return (($byte & 31) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }

        if (($byte & 240) === 224) {
            return (($byte & 15) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }

        return (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
    }

    protected function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        if ($length < 0x4000) {
            $length |= 0x8000;

            return chr(($length >> 8) & 0xFF).chr($length & 0xFF);
        }

        if ($length < 0x200000) {
            $length |= 0xC00000;

            return chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF);
        }

        if ($length < 0x10000000) {
            $length |= 0xE0000000;

            return chr(($length >> 24) & 0xFF).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF);
        }

        return chr(0xF0).chr(($length >> 24) & 0xFF).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF);
    }

    protected function parseResponse(array $response): array
    {
        $parsed = [];
        $current = null;

        foreach ($response as $line) {
            if ($line === '!re') {
                $parsed[] = [];
                $current = array_key_last($parsed);

                continue;
            }

            if (in_array($line, ['!done', '!fatal'], true)) {
                continue;
            }

            if ($line === '!trap') {
                $parsed['!trap'][] = [];
                $current = '!trap';

                continue;
            }

            if ($line[0] === '=' && preg_match('/^=([^=]+)=(.*)$/', $line, $matches)) {
                if ($current === null) {
                    $parsed[] = [];
                    $current = array_key_last($parsed);
                }

                if ($current === '!trap') {
                    $trapIndex = array_key_last($parsed['!trap']);
                    $parsed['!trap'][$trapIndex][$matches[1]] = $matches[2];
                } else {
                    $parsed[$current][$matches[1]] = $matches[2];
                }
            }
        }

        return $parsed;
    }

    protected function throwIfTrap(array $response): void
    {
        if (isset($response['!trap'])) {
            $message = $response['!trap'][0]['message'] ?? 'MikroTik command failed.';

            throw new RuntimeException($message);
        }
    }
}
