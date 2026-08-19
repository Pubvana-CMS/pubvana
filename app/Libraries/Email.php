<?php

namespace App\Libraries;

use CodeIgniter\Email\Email as CI4Email;

/**
 * Extends CI4's Email class to route sends through a plugin email provider
 * when one is active. All native setTo(), setFrom(), setSubject() etc. calls
 * remain untouched — only send() is overridden.
 *
 * If a plugin's handleEmail() returns false (or no provider is active),
 * delivery falls through to parent::send() as normal.
 */
class Email extends CI4Email
{
    public function send(bool $autoClear = true): bool
    {
        $provider = service('emailProvider')->getProvider();

        if ($provider === null) {
            return parent::send($autoClear);
        }

        $data = [
            'to'          => $this->recipients,
            'from'        => $this->tmpArchive['fromEmail'] ?? $this->fromEmail ?? '',
            'fromName'    => $this->tmpArchive['fromName']  ?? $this->fromName  ?? '',
            'subject'     => $this->tmpArchive['subject']   ?? '',
            'body'        => $this->body,
            'altMessage'  => $this->altMessage,
            'cc'          => $this->tmpArchive['CCArray']   ?? $this->CCArray   ?? [],
            'bcc'         => array_unique(array_merge(
                                 $this->BCCArray                    ?? [],
                                 $this->tmpArchive['BCCArray']      ?? []
                             )),
            'replyTo'     => $this->tmpArchive['replyTo']   ?? null,
            'replyToName' => $this->tmpArchive['replyName'] ?? null,
            'attachments' => $this->attachments,
        ];

        try {
            $handled = $provider->handleEmail($data);
        } catch (\Throwable $e) {
            log_message('error', 'EmailProvider::handleEmail() threw an exception: ' . $e->getMessage());
            $handled = false;
        }

        if ($handled) {
            if ($autoClear) {
                $this->clear();
            }

            return true;
        }

        return parent::send($autoClear);
    }
}
