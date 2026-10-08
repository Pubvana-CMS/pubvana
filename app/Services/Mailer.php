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
 * Mail.enabled (Settings > Email) gates the service. While it is off no
 * send is attempted: the attempt goes to the error log, no mail_logs row
 * is written, and the call returns without throwing.
 * The test probe is gated the same way.
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
        if (!$this->enabled()) {
            $this->logSkipped($to, $subject);
            return;
        }

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
        $siteName = (string) $this->settings->get('CMS.siteName');
        $subject = 'Test message from ' . $siteName;

        if (!$this->enabled()) {
            $this->logSkipped($to, $subject);
            return ['ok' => false, 'debug' => '', 'error' => 'Email sending is disabled'];
        }

        $from = $this->fromDefaults([]);

        try {
            $mail = $this->transport();
            $this->captureDebug($mail, $buffer);
            $mail->setFrom($from['address'], $from['name']);
            $mail->addAddress($to);
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

        $this->log($to, $subject, 'failed', $error, $from['address']);

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

    /**
     * Whether outbound mail is switched on.
     *
     * Public form of enabled(), for callers that must decide before they
     * commit to work that only pays off if a message actually goes out, such
     * as rotating a password reset token.
     */
    public function isEnabled(): bool
    {
        return $this->enabled();
    }

    // -----------------------------------------------------------------
    // Internal Helpers
    // -----------------------------------------------------------------

    /**
     * Whether outbound mail is switched on. Off is off: nothing is sent,
     * the test probe included.
     */
    protected function enabled(): bool
    {
        return (bool) $this->settings->get('Mail.enabled');
    }

    /**
     * Write a skipped-send line to the error log.
     */
    protected function logSkipped(string $to, string $subject): void
    {
        error_log(
            (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            . ' [mail] sending disabled, skipped: ' . $to . ' "' . $subject . '"'
        );
    }

    /**
     * Build a configured PHPMailer instance. SMTP is the only transport.
     */
    protected function transport(): PHPMailer
    {
        $mail = new PHPMailer(true); // exceptions
        $mail->isSMTP();

        $host = (string) $this->settings->get('Mail.host');
        $this->assertSafeSmtpHost($host);
        $mail->Host = $host;
        $mail->Port = (int) $this->settings->get('Mail.port');

        $encryption = (string) $this->settings->get('Mail.encryption');
        $mail->SMTPSecure = $encryption === 'none' ? '' : $encryption;

        $username = (string) $this->settings->get('Mail.username');
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
     * Refuse an SMTP host that points at the local machine or a link-local
     * address. Those are the high-value SSRF targets (loopback services and
     * the cloud metadata endpoint at 169.254.169.254) and are never a real
     * mail server. Private ranges (10/8, 172.16/12, 192.168/16) stay allowed
     * so an internal relay still works, and development is exempt so a local
     * test relay (mailhog) keeps working.
     *
     * @throws MailerException When the stored host is a loopback, link-local,
     *                         or otherwise reserved address.
     */
    protected function assertSafeSmtpHost(string $host): void
    {
        if (($this->app->get('environment') ?? 'production') === 'development') {
            return;
        }

        $host = trim($host);
        if ($host === '') {
            return;
        }

        // Strip the brackets PHPMailer accepts around a literal IPv6 host.
        $bare = trim($host, '[]');

        if (strcasecmp($bare, 'localhost') === 0) {
            throw new MailerException('SMTP host "localhost" is not allowed.');
        }

        if (filter_var($bare, FILTER_VALIDATE_IP) === false) {
            return; // A hostname: left to DNS, same as before.
        }

        // NO_RES_RANGE covers loopback (127/8, ::1), link-local (169.254/16,
        // fe80::/10), and other reserved blocks, but not the private ranges.
        if (filter_var($bare, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new MailerException('SMTP host is a loopback or link-local address.');
        }
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
        $address = $opts['from'] ?? (string) $this->settings->get('Mail.fromEmail');
        $name = $opts['fromName'] ?? (string) $this->settings->get('Mail.fromName');

        if ($address === '') {
            $address = (string) $this->settings->get('CMS.adminEmail');
        }
        if ($name === '') {
            $name = (string) $this->settings->get('CMS.siteName');
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
        $stored = $this->settings->get('Mail.password');
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
     * Encryption moved to SecretCipher (v2 layout: Encrypt-then-MAC).
     * These wrappers keep the internal call sites unchanged and the v1
     * legacy rows decrypting; a re-save upgrades a row to the MAC layout.
     */
    protected function encrypt(string $plain): string
    {
        return $this->cipher()->encrypt($plain);
    }

    /**
     * Decrypt a stored value, returning null when it cannot be read.
     */
    protected function decrypt(string $payload): ?string
    {
        return $this->cipher()->decrypt($payload);
    }

    private SecretCipher|null $secretCipher = null;

    private function cipher(): SecretCipher
    {
        if ($this->secretCipher === null) {
            $this->secretCipher = new SecretCipher($this->app);
        }
        return $this->secretCipher;
    }
}