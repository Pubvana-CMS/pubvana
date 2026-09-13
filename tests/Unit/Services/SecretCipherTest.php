<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\TestCase;
use Pubvana\Services\SecretCipher;

/**
 * SecretCipher stores secrets at rest with authenticated encryption
 * (AUDIT M9): v2 payloads carry an HMAC over IV||ciphertext, tampering
 * fails closed, and legacy v1 rows (no MAC) still decrypt.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(SecretCipher::class)]
final class SecretCipherTest extends TestCase
{
    private const KEY = 'test-deployment-key-0123456789';

    private Engine $app;

    protected function setUp(): void
    {
        $this->app = new Engine();
        $this->app->set('plugins', [
            'enlivenapp/flight-sessions' => ['encryption_key' => self::KEY],
        ]);
    }

    public function testRoundTrip(): void
    {
        $cipher = new SecretCipher($this->app);

        $stored = $cipher->encrypt('smtp-secret-pa55w0rd');

        self::assertStringStartsWith('v2:', $stored);
        self::assertStringNotContainsString('smtp-secret', $stored);
        self::assertSame('smtp-secret-pa55w0rd', $cipher->decrypt($stored));
    }

    public function testCiphertextIsRandomizedPerCall(): void
    {
        $cipher = new SecretCipher($this->app);

        self::assertNotSame($cipher->encrypt('same'), $cipher->encrypt('same'));
    }

    public function testTamperedPayloadFailsClosed(): void
    {
        $cipher = new SecretCipher($this->app);

        $stored = $cipher->encrypt('secret');
        $raw = base64_decode(substr($stored, 3), true);
        self::assertIsString($raw);
        // Flip one bit in the ciphertext body.
        $raw[20] = $raw[20] ^ "\x01";
        $tampered = 'v2:' . base64_encode($raw);

        self::assertNull($cipher->decrypt($tampered));
    }

    public function testTruncatedPayloadFailsClosed(): void
    {
        $cipher = new SecretCipher($this->app);

        $stored = $cipher->encrypt('secret');
        $raw = base64_decode(substr($stored, 3), true);
        self::assertIsString($raw);
        $truncated = 'v2:' . base64_encode(substr($raw, 0, 20));

        self::assertNull($cipher->decrypt($truncated));
    }

    public function testWrongKeyFailsClosed(): void
    {
        $cipher = new SecretCipher($this->app);
        $stored = $cipher->encrypt('secret');

        $other = new Engine();
        $other->set('plugins', [
            'enlivenapp/flight-sessions' => ['encryption_key' => 'a-completely-different-key'],
        ]);

        self::assertNull((new SecretCipher($other))->decrypt($stored));
    }

    public function testLegacyV1RowsStillDecrypt(): void
    {
        // Rebuild the historical v1 layout (base64(IV||cipher), no MAC)
        // with the same key derivation Mailer used before SecretCipher.
        $cipherKey = hash_hmac('sha256', 'pubvana.mail.v1', self::KEY, true);
        $iv = random_bytes(16);
        $cipherText = openssl_encrypt('legacy-password', 'aes-256-cbc', $cipherKey, OPENSSL_RAW_DATA, $iv);
        self::assertIsString($cipherText);
        $legacy = base64_encode($iv . $cipherText);

        $cipher = new SecretCipher($this->app);

        self::assertSame('legacy-password', $cipher->decrypt($legacy));
    }

    public function testGarbageReturnsNull(): void
    {
        $cipher = new SecretCipher($this->app);

        self::assertNull($cipher->decrypt('not-a-payload'));
    }

    public function testMissingKeyThrows(): void
    {
        $app = new Engine();
        unset($_ENV['SESSION_ENCRYPTION_KEY']);
        putenv('SESSION_ENCRYPTION_KEY');
        try {
            $this->expectException(\RuntimeException::class);
            (new SecretCipher($app))->encrypt('secret');
        } finally {
            putenv('SESSION_ENCRYPTION_KEY=' . self::KEY);
        }
    }
}
