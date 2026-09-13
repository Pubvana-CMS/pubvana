<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * SecretCipher - authenticated encryption for secrets stored in settings.
 *
 * Single owner of at-rest secret encryption (SMTP password, captcha secret
 * key, anything else a feature needs to keep out of plaintext storage).
 *
 * Layout v2: 'v2:' . base64( IV(16 bytes) || ciphertext || HMAC-SHA256 ).
 * Encrypt-then-MAC over the raw AES-256-CBC output, with an independent
 * MAC key; a tampered, truncated, or wrong-key payload fails closed
 * (decrypt returns null, never garbage). Legacy v1 rows (base64 of
 * IV || ciphertext, no MAC) still decrypt so existing settings keep
 * working until they are re-saved through the admin.
 *
 * Both keys derive from SESSION_ENCRYPTION_KEY via HKDF-style HMAC info
 * strings, so secrets share the deployment-level key and never require
 * another piece of infrastructure.
 *
 * @package Pubvana\Services
 */
class SecretCipher
{
    /** Marks the MAC-protected layout. */
    private const FORMAT_PREFIX = 'v2:';

    /**
     * Cipher-key info. Kept at the historical Mailer value so v1 rows
     * decrypt without a migration.
     */
    private const CIPHER_KEY_INFO = 'pubvana.mail.v1';

    /** Independent MAC-key info. */
    private const MAC_KEY_INFO = 'pubvana.mail.mac.v1';

    /**
     * @param Engine<object> $app
     */
    public function __construct(private Engine $app)
    {
    }

    /**
     * Encrypt a plaintext value for storage (v2 layout).
     *
     * @throws \RuntimeException when no encryption key is available or the
     *                           cipher fails
     */
    public function encrypt(string $plain): string
    {
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $this->cipherKey(), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('SecretCipher: encryption failed.');
        }
        $mac = hash_hmac('sha256', $iv . $cipher, $this->macKey(), true);
        return self::FORMAT_PREFIX . base64_encode($iv . $cipher . $mac);
    }

    /**
     * Decrypt a stored value, returning null when it cannot be read or
     * fails the integrity check.
     */
    public function decrypt(string $payload): ?string
    {
        if (str_starts_with($payload, self::FORMAT_PREFIX)) {
            return $this->decryptV2(substr($payload, strlen(self::FORMAT_PREFIX)));
        }
        return $this->decryptLegacy($payload);
    }

    /**
     * v2: base64( IV || ciphertext || MAC ), verified before decryption.
     */
    private function decryptV2(string $encoded): ?string
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 16 + 32) {
            return null;
        }
        $iv = substr($raw, 0, 16);
        $mac = substr($raw, -32);
        $cipher = substr($raw, 16, -32);
        if (!hash_equals($mac, hash_hmac('sha256', $iv . $cipher, $this->macKey(), true))) {
            error_log('SecretCipher: stored secret failed the integrity check - treating as unset.');
            return null;
        }
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', $this->cipherKey(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }

    /**
     * v1 (legacy): base64( IV || ciphertext ), no MAC. Kept so rows saved
     * before the MAC existed keep working; re-saving upgrades them.
     */
    private function decryptLegacy(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 16) {
            return null;
        }
        $iv = substr($raw, 0, 16);
        $plain = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $this->cipherKey(), OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            error_log('SecretCipher: stored secret could not be decrypted - treating as unset.');
            return null;
        }
        return $plain;
    }

    /**
     * Site encryption key: derived from SESSION_ENCRYPTION_KEY so secrets
     * share the deployment-level key and never require another piece of
     * infrastructure.
     */
    private function encryptionKey(): string
    {
        $plugins = $this->app->get('plugins');
        $key = is_array($plugins)
            ? ($plugins['enlivenapp/flight-sessions']['encryption_key'] ?? null)
            : null;
        if (!is_string($key) || $key === '') {
            $key = $_ENV['SESSION_ENCRYPTION_KEY'] ?? (getenv('SESSION_ENCRYPTION_KEY') ?: null);
        }
        if (!is_string($key) || $key === '') {
            throw new \RuntimeException('SecretCipher: SESSION_ENCRYPTION_KEY is not available for secret encryption.');
        }
        return $key;
    }

    /**
     * 256-bit cipher key for stored secrets.
     */
    private function cipherKey(): string
    {
        return hash_hmac('sha256', self::CIPHER_KEY_INFO, $this->encryptionKey(), true);
    }

    /**
     * Independent 256-bit MAC key (never the cipher key itself).
     */
    private function macKey(): string
    {
        return hash_hmac('sha256', self::MAC_KEY_INFO, $this->encryptionKey(), true);
    }
}
