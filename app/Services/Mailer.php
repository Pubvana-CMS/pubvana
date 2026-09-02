<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use Pubvana\Models\Mail;

/**
 * Mailer - SMTP mail service.
 *
 * The workhorse behind every outbound message: resolves the SMTP
 * configuration from the settings store, builds a PHPMailer message,
 * sends it, and records the attempt (sent/failed) in the mail_logs
 * table via the Mail model. SMTP is the only transport.
 *
 * Secrets: the SMTP password is stored ENCRYPTED at rest in the
 * settings table (AES-256-CBC, key derived from the site's
 * SESSION_ENCRYPTION_KEY). It is decrypted only here, inside the
 * service, and never surfaces to controllers or views.
 *
 * @package Pubvana\Services
 */
class Mailer
{

    /** @var Engine<object> The FlightPHP app instance */
    protected Engine $app;
    protected \Pubvana\Services\SettingsService $settings;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->settings = $app->settings();
    }

    /**
     * Send an HTML message.
     *
     * @param string                                $to      Recipient address
     * @param string                                $subject Message subject
     * @param string                                $bodyHtml HTML body
     * @param array{from?: string, fromName?: string, alt?: string, replyTo?: string} $opts Optional overrides
     * @throws \RuntimeException When the send fails
     */
    public function sendHtml(string $to, string $subject, string $bodyHtml, array $opts = []): void
    {
        $from = $this->fromDefaults($opts);

        try {
            $mail = $this->transport();
            $mail->setFrom($from['address'], $from['name']);
            $mail->addAddress($to);
            if (isset($opts['replyTo']) && $opts['replyTo'] !== '') {
                $mail->addReplyTo($opts['replyTo']);
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $bodyHtml;
            if (isset($opts['alt']) && $opts['alt'] !== '') {
                $mail->AltBody = $opts['alt'];
            }

            if (!$mail->send()) {
                throw new \RuntimeException($mail->ErrorInfo);
            }

            $this->log($to, $subject, 'sent', null, $from['address']);
        } catch (MailerException $e) {
            $this->log($to, $subject, 'failed', $e->getMessage(), $from['address']);
            throw new \RuntimeException('Mailer: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a probe message and return debug/error detail.
     *
     * @param string $to Recipient address for the probe
     * @return array{ok: bool, debug: string, error: ?string}
     */
    public function test(string $to): array
    {
        $buffer = '';
        $from = $this->fromDefaults([]);

        try {
            $mail = $this->transport();
            $this->captureDebug($mail, $buffer);
            $mail->setFrom($from['address'], $from['name']);
            $mail->addAddress($to);
            $siteName = (string) ($this->app->get('CMS.siteName') ?? 'Pubvana');
            $subject = 'Test message from ' . $siteName;
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = '<p>This is a test email. If you can read this, SMTP is configured correctly.</p>';

            if ($mail->send()) {
                $this->log($to, $subject, 'sent', null, $from['address']);
                return ['ok' => true, 'debug' => $buffer, 'error' => null];
            }

            $error = $mail->ErrorInfo;
        } catch (MailerException $e) {
            $error = $e->getMessage();
        }

        $this->log($to, $subject ?? 'Test message', 'failed', $error, $from['address']);

        return ['ok' => false, 'debug' => $buffer, 'error' => $error];
    }

    /**
     * Validate, coerce, and persist the email settings posted from the
     * admin Email page. The SMTP password is encrypted before storage;
     * a blank posted password keeps whatever is already stored.
     *
     * @param array<string, mixed> $post Raw posted values, keyed by full setting key
     * @return array{saved: int, rejected: list<string>}
     */
    public function saveSettings(array $post): array
    {
        $declared = [];
        foreach ($this->app->adext()->get('admin.settings', 'email') as $contributor => $tab) {
            foreach (($tab['fields'] ?? []) as $field) {
                $declared[$field['key']] = $field;
            }
        }

        $saved = 0;
        $rejected = [];

        foreach ($declared as $key => $field) {
            $type = $field['type'] ?? 'text';
            $has = array_key_exists($key, $post);

            if ($key === 'Mail.password') {
                $plain = isset($post[$key]) && is_string($post[$key]) ? trim($post[$key]) : '';
                if ($plain === '') {
                    continue; // blank = keep the stored value, never clobber
                }
                try {
                    $this->settings->set($key, $this->encrypt($plain));
                    $saved++;
                } catch (\Throwable $e) {
                    error_log('Mailer::saveSettings rejected "Mail.password" - ' . $e->getMessage());
                    $rejected[] = $field['label'] ?? $key;
                }
                continue;
            }

            if (!$has) {
                if ($type === 'checkbox') {
                    $this->settings->set($key, false);
                    $saved++;
                }
                continue;
            }

            try {
                $this->settings->set($key, $this->coerce($field, $post[$key]));
                $saved++;
            } catch (\Throwable $e) {
                error_log('Mailer::saveSettings rejected "' . $key . '" - ' . $e->getMessage());
                $rejected[] = $field['label'] ?? $key;
            }
        }

        return ['saved' => $saved, 'rejected' => $rejected];
    }

    /**
     * Latest outbound attempts for the admin recent-sends list.
     *
     * @return \Pubvana\Models\Mail[]
     */
    public function recent(int $limit = 15): array
    {
        return (new Mail($this->app->db()))->recent($limit);
    }

    // -----------------------------------------------------------------
    // Internal Helpers
    // -----------------------------------------------------------------

    /**
     * Build a configured PHPMailer instance. SMTP is the only transport.
     */
    protected function transport(): PHPMailer
    {
        $mail = new PHPMailer(true); // exceptions
        $mail->isSMTP();

        $mail->Host = (string) ($this->settings->get('Mail.host', 'localhost') ?? 'localhost');
        $mail->Port = (int) ($this->settings->get('Mail.port', 587) ?? 587);

        $encryption = (string) ($this->settings->get('Mail.encryption', 'tls') ?? 'tls');
        $mail->SMTPSecure = $encryption === 'none' ? '' : $encryption;

        $username = (string) ($this->settings->get('Mail.username', '') ?? '');
        $password = $this->password();
        if ($username !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
        }

        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 10;
        $mail->SMTPAutoTLS = true;

        return $mail;
    }

    /**
     * Enable SMTP-level debug capture on a transport, appending each debug
     * line to the given buffer. Call before send().
     */
    protected function captureDebug(PHPMailer $mail, string &$buffer): void
    {
        $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
        $mail->Debugoutput = function (string $line, int $level) use (&$buffer): void {
            $buffer .= $line;
        };
    }

    /**
     * Resolve the effective From address/name for a message.
     *
     * @param array{from?: string, fromName?: string} $opts Optional per-call 'from' / 'fromName' overrides
     * @return array{address: string, name: string}
     */
    protected function fromDefaults(array $opts): array
    {
        $address = $opts['from'] ?? ($this->settings->get('Mail.fromEmail', '') ?? '');
        $name = $opts['fromName'] ?? ($this->settings->get('Mail.fromName', '') ?? '');

        if (!is_string($address) || $address === '') {
            $address = (string) ($this->app->get('CMS.adminEmail') ?? 'no-reply@localhost');
        }
        if (!is_string($name) || $name === '') {
            $name = (string) ($this->app->get('CMS.siteName') ?? 'Pubvana');
        }

        return ['address' => $address, 'name' => $name];
    }

    /**
     * Decrypted SMTP password, or '' when none is configured.
     *
     * Values that do not decrypt (raw/unrecognized) are treated as unset
     * so a stray value can never be handed to the transport.
     */
    protected function password(): string
    {
        $stored = $this->settings->get('Mail.password', null);
        if (!is_string($stored) || $stored === '') {
            return '';
        }
        return $this->decrypt($stored) ?? '';
    }

    /**
     * Record a send attempt in mail_logs. Failures here must never
     * break the send itself - log and move on.
     */
    protected function log(string $to, string $subject, string $status, ?string $error, string $fromAddress): void
    {
        try {
            (new Mail($this->app->db()))->record($to, $subject, $status, $error, $fromAddress);
        } catch (\Throwable $e) {
            error_log('Mailer: unable to record mail log row - ' . $e->getMessage());
        }
    }

    /**
     * Coerce a raw posted value to the declared field type.
     *
     * @param array<string, mixed> $field Field declaration
     * @param mixed                $raw   Posted value
     * @return mixed Properly typed value for storage
     * @throws \InvalidArgumentException When validation fails
     */
    protected function coerce(array $field, mixed $raw): mixed
    {
        $value = is_string($raw) ? trim($raw) : $raw;

        switch ($field['type']) {
            case 'number':
                if (is_numeric($value)) {
                    return $value + 0;
                }
                throw new \InvalidArgumentException('not a number');

            case 'checkbox':
                return filter_var($value, FILTER_VALIDATE_BOOL);

            case 'email':
                $email = filter_var((string) $value, FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    throw new \InvalidArgumentException('not a valid email address');
                }
                return $email;

            case 'select':
                $options = array_map('strval', array_keys((array) ($field['options'] ?? [])));
                if (!in_array((string) $value, $options, true)) {
                    throw new \InvalidArgumentException('value not in options');
                }
                return $value;

            default: // text, textarea, password
                return (string) $value;
        }
    }

    // -----------------------------------------------------------------
    // Encryption (at rest)
    // -----------------------------------------------------------------

    /**
     * Site encryption key: derived from SESSION_ENCRYPTION_KEY so mail
     * secrets share the deployment-level key and never require another
     * piece of infrastructure.
     */
    protected function encryptionKey(): string
    {
        $plugins = $this->app->get('plugins');
        $key = is_array($plugins)
            ? ($plugins['enlivenapp/flight-sessions']['encryption_key'] ?? null)
            : null;
        if (!is_string($key) || $key === '') {
            $key = $_ENV['SESSION_ENCRYPTION_KEY'] ?? (getenv('SESSION_ENCRYPTION_KEY') ?: null);
        }
        if (!is_string($key) || $key === '') {
            throw new \RuntimeException('Mailer: SESSION_ENCRYPTION_KEY is not available for password encryption.');
        }
        return $key;
    }

    /**
     * 256-bit cipher key for mail secrets.
     */
    protected function cipherKey(): string
    {
        return hash_hmac('sha256', 'pubvana.mail.v1', $this->encryptionKey(), true);
    }

    /**
     * Encrypt a plaintext value for storage.
     *
     * Layout: base64( IV || ciphertext ).
     */
    protected function encrypt(string $plain): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $this->cipherKey(), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('Mailer: encryption failed.');
        }
        return base64_encode($iv . $cipher);
    }

    /**
     * Decrypt a stored value, returning null when it cannot be read.
     */
    protected function decrypt(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 16) {
            return null;
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', $this->cipherKey(), OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            error_log('Mailer: stored Mail.password could not be decrypted - treating as unset.');
            return null;
        }
        return $plain;
    }
}