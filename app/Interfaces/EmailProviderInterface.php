<?php

namespace App\Interfaces;

interface EmailProviderInterface
{
    /**
     * Handle sending a core system email.
     *
     * Receives the assembled email data and returns true if the plugin
     * handled delivery. Return false to fall through to the core email handler.
     *
     * @param array{
     *     to:          string[],
     *     from:        string,
     *     fromName:    string,
     *     subject:     string,
     *     body:        string,
     *     altMessage:  string,
     *     cc:          string[],
     *     bcc:         string[],
     *     replyTo:     string|null,
     *     replyToName: string|null,
     *     attachments: array
     * } $data
     */
    public function handleEmail(array $data): bool;
}
