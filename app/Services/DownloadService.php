<?php

namespace App\Services;

class DownloadService
{
    /**
     * Download a file from a URL to a local path.
     *
     * Fallback chain:
     *   1. cURL extension
     *   2. file_get_contents (requires allow_url_fopen)
     *
     * Returns true on success, false on failure.
     * If neither method is available, returns false (controller should offer manual upload).
     */
    public function download(string $url, string $destPath): bool
    {
        $dir = dirname($destPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Method 1: cURL
        if (extension_loaded('curl')) {
            $result = $this->downloadViaCurl($url, $destPath);
            if ($result) {
                return true;
            }
        }

        // Method 2: file_get_contents
        if (ini_get('allow_url_fopen')) {
            $result = $this->downloadViaStream($url, $destPath);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return which download methods are available on this server.
     *
     * @return string[]  e.g. ['curl', 'stream']
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        if (extension_loaded('curl')) {
            $methods[] = 'curl';
        }
        if (ini_get('allow_url_fopen')) {
            $methods[] = 'stream';
        }
        return $methods;
    }

    /**
     * Check if any download method is available (if not, manual upload is required).
     */
    public function canDownload(): bool
    {
        return ! empty($this->getAvailableMethods());
    }

    private function downloadViaCurl(string $url, string $destPath): bool
    {
        $fh = fopen($destPath, 'w');
        if (! $fh) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE            => $fh,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_TIMEOUT         => 300,
            CURLOPT_USERAGENT       => 'Pubvana-CMS/' . APP_VERSION,
            CURLOPT_HTTPHEADER      => ['Accept: application/octet-stream'],
        ]);

        $ok   = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);

        if (! $ok || $code >= 400) {
            @unlink($destPath);
            return false;
        }

        return true;
    }

    private function downloadViaStream(string $url, string $destPath): bool
    {
        $context = stream_context_create([
            'http' => [
                'timeout'    => 300,
                'header'     => "User-Agent: Pubvana-CMS/" . APP_VERSION . "\r\n"
                              . "Accept: application/octet-stream\r\n",
                'follow_location' => true,
                'max_redirects'   => 5,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            return false;
        }

        return file_put_contents($destPath, $data) !== false;
    }
}
