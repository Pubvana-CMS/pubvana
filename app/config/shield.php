<?php

/**
 * Pubvana's default configuration for flight-shield.
 *
 * A copy of vendor/enlivenapp/flight-shield/src/Config/Config.php with
 * Pubvana's defaults applied, so edits stay local to this application.
 * The hmac block starts blank; it is managed in place via:
 *
 *   php runway shield:hmac init|listkeys|addkey|removekey
 *
 * Downstream sites can change anything in this file.
 */

return [
    'routePrepend' => 'auth',
    // Default authenticator
    'default_authenticator' => 'session',

    // Available authenticators
    'authenticators' => [
        'session' => \Enlivenapp\FlightShield\Authentication\Authenticators\Session::class,
        'tokens'  => \Enlivenapp\FlightShield\Authentication\Authenticators\AccessTokens::class,
        'hmac'    => \Enlivenapp\FlightShield\Authentication\Authenticators\HmacSha256::class,
        // 'jwt'  => \Enlivenapp\FlightShield\Authentication\Authenticators\JWT::class,
    ],

    // Authentication chain — tried in order by ChainAuthMiddleware
    'authentication_chain' => ['session', 'tokens', 'hmac'],

    // JWT settings (requires firebase/php-jwt)
    'jwt' => [
        'header'         => 'Authorization',
        'time_to_live'   => 3600, // 1 hour
        'default_claims' => [
            'iss' => '',  // Issuer — set to your domain
        ],
        'keys' => [
            'default' => [
                [
                    'kid'    => '',       // Key ID (optional with single key)
                    'alg'    => 'HS256',  // HS256, HS384, HS512, RS256, etc.
                    'secret' => '',       // Symmetric key (min 256 bits for HS256)
                    // Asymmetric: use 'public', 'private', 'passphrase' instead
                ],
            ],
        ],
    ],

    // HMAC settings, managed via: runway shield:hmac
    // Blank by default; init writes keys into this block.
    'hmac' => [
        'encryption_keys' => [ ],
        'encryption_current_key' => '',
        'encryption_cipher' => 'aes-256-gcm',
        'secret2_storage_limit' => 255,
    ],

    // Session-based auth settings
    'session' => [
        'field'                => 'user',
        'allow_remembering'    => true,
        'remember_cookie_name' => 'remember',
        'remember_length'      => 30 * 86400, // 30 days
    ],

    // Password settings
    'passwords' => [
        'algorithm'      => PASSWORD_DEFAULT,
        'cost'           => 12,
        'memory_cost'    => 65536,
        'time_cost'      => 4,
        'threads'        => 1,
        'min_length'     => 8,
        'max_similarity' => 50,
        'validators'     => [
            \Enlivenapp\FlightShield\Passwords\CompositionValidator::class,
            \Enlivenapp\FlightShield\Passwords\NothingPersonalValidator::class,
            \Enlivenapp\FlightShield\Passwords\DictionaryValidator::class,
            // \Enlivenapp\FlightShield\Passwords\PwnedValidator::class,
        ],
    ],

    // Token settings (access tokens and HMAC)
    'token_header'          => 'Authorization',
    'hmac_header'           => 'Authorization',
    'unused_token_lifetime' => 3600 * 24 * 90, // 90 days

    // Rate limiting for login, 2FA, and magic link endpoints
    'rate_limiting' => [
        'enabled'         => true,
        'max_attempts'    => 10,
        'decay_minutes'   => 30,
        'lockout_minutes' => 30,
    ],

    // Login attempt recording: 'none', 'failure', 'all'
    'record_login_attempt' => 'all',

    // Update last_active on every authenticated request
    'record_active_date' => true,

    // Allow new user registration
    'allow_registration' => true,

    // Magic link login
    'allow_magic_link'    => false,
    'magic_link_lifetime' => 3600, // 1 hour

    // Email callback — shield calls this to send emails
    // function(string $to, string $subject, string $body): void
    'email_sender' => function (string $to, string $subject, string $body): void {
        \Flight::mailer()->sendHtml($to, $subject, $body);
    },

    // Authentication actions (post-login / post-register)
    // Set to a class implementing ActionInterface, or null to disable
    'actions' => [
        'login'    => null, // e.g. \Enlivenapp\FlightShield\Authentication\Actions\Email2FA::class
        'register' => null, // e.g. \Enlivenapp\FlightShield\Authentication\Actions\EmailActivator::class
    ],

    // Valid fields for login
    'valid_login_fields' => ['email'],

    // Personal fields (checked by NothingPersonalValidator)
    'personal_fields' => [],

    // Default group for new users
    'default_group' => 'user',

    // Redirect URLs
    'redirects' => [
        'login'             => '/auth/login',
        'logout'            => '/',
        'after_login'       => '/',
        'after_login_admin' => '/admin',
        'after_register'    => '/',
        'after_logout'      => '/auth/login',
        'force_reset'       => '/auth/reset-password',
        'permission_denied' => '/auth/login',
        'group_denied'      => '/auth/login',
    ],
];