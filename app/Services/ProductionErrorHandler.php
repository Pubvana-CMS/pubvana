<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * Production error responses for uncaught exceptions.
 *
 * The full exception detail is written to writable/logs/exception.log; the
 * client only ever receives a generic message, so exception text (file paths,
 * credentials, stack frames) is never leaked. The development re-throw (Tracy)
 * stays in the config error handler; this service is only used in production.
 */
class ProductionErrorHandler
{
    /** Client-facing message; the real detail stays in the log. */
    private const PUBLIC_MESSAGE = 'Something went wrong. Please try again later.';

    /**
     * @param string $logFile Where exception detail is appended, defaulting to
     *                        writable/logs/exception.log
     */
    public function __construct(
        private readonly string $logFile = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'exception.log'
    ) {
    }

    /**
     * Log the exception and build the sanitized response payload.
     *
     * @param Engine<object> $app
     *
     * @return array{status: int, json: bool, message: string}
     */
    public function handle(\Throwable $e, Engine $app): array
    {
        $this->log($e);

        return [
            'status'  => $this->status($e),
            'json'    => $this->wantsJson($app),
            'message' => self::PUBLIC_MESSAGE,
        ];
    }

    /**
     * @param Engine<object> $app
     */
    private function wantsJson(Engine $app): bool
    {
        $request = $app->request();

        return str_contains($request->getHeader('Accept'), 'application/json')
            || str_contains($request->getHeader('Content-Type'), 'application/json')
            || $request->ajax;
    }

    private function status(\Throwable $e): int
    {
        if (method_exists($e, 'getHttpStatus')) {
            return $e->getHttpStatus();
        }

        return 500;
    }

    private function log(\Throwable $e): void
    {
        $class = get_class($e);
        $line = '[' . date('Y-m-d H-i-s') . "] [{$class}] {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}" . PHP_EOL;
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}