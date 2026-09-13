<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\ProductionErrorHandler;
use Pubvana\Tests\Support\TestCase;

/**
 * ProductionErrorHandler tests.
 *
 * The handler is the production branch of the global error handler. The full
 * exception message must never reach the response payload; it only goes to the
 * log file. Status codes come from getHttpStatus() when the exception carries
 * one, and the JSON/HTML split follows the request's Accept header and ajax
 * flag.
 */
#[CoversClass(ProductionErrorHandler::class)]
final class ProductionErrorHandlerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'pubvana-error-');
        self::assertNotFalse($this->logFile);
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);
    }

    public function testResponseNeverContainsTheExceptionMessage(): void
    {
        $exception = new \RuntimeException('db password=supersecret at /etc/app/.env');
        $handler = new ProductionErrorHandler($this->logFile);
        $app = $this->app(['request' => fn () => new FakeRequest()]);

        $payload = $handler->handle($exception, $app);

        self::assertStringNotContainsString($exception->getMessage(), $payload['message']);
        self::assertSame('Something went wrong. Please try again later.', $payload['message']);
    }

    public function testExceptionDetailIsLoggedToFile(): void
    {
        $exception = new \RuntimeException('Failed to connect: root:secret@127.0.0.1');
        $handler = new ProductionErrorHandler($this->logFile);
        $app = $this->app(['request' => fn () => new FakeRequest()]);

        $handler->handle($exception, $app);

        $log = (string) file_get_contents($this->logFile);
        self::assertStringContainsString($exception->getMessage(), $log);
        self::assertStringContainsString(\RuntimeException::class, $log);
    }

    public function testDefaultsToInternalServerError(): void
    {
        $handler = new ProductionErrorHandler($this->logFile);
        $app = $this->app(['request' => fn () => new FakeRequest()]);

        $payload = $handler->handle(new \RuntimeException('boom'), $app);

        self::assertSame(500, $payload['status']);
    }

    public function testUsesHttpStatusFromTypedException(): void
    {
        $handler = new ProductionErrorHandler($this->logFile);
        $app = $this->app(['request' => fn () => new FakeRequest()]);

        $payload = $handler->handle(new HttpStatusException(404), $app);

        self::assertSame(404, $payload['status']);
    }

    public function testJsonWhenAcceptHeaderWantsJson(): void
    {
        $handler = new ProductionErrorHandler($this->logFile);
        $app = $this->app([
            'request' => fn () => new FakeRequest(['Accept' => 'application/json']),
        ]);

        $payload = $handler->handle(new \RuntimeException('boom'), $app);

        self::assertTrue($payload['json']);
    }

    public function testHtmlWhenNoJsonRequested(): void
    {
        $handler = new ProductionErrorHandler($this->logFile);
        $app = $this->app(['request' => fn () => new FakeRequest()]);

        $payload = $handler->handle(new \RuntimeException('boom'), $app);

        self::assertFalse($payload['json']);
    }

    public function testJsonWhenAjaxFlagIsSet(): void
    {
        $request = new FakeRequest();
        $request->ajax = true;
        $handler = new ProductionErrorHandler($this->logFile);
        $app = $this->app(['request' => fn () => $request]);

        $payload = $handler->handle(new \RuntimeException('boom'), $app);

        self::assertTrue($payload['json']);
    }
}

/**
 * Request stand-in: exposes getHeader() and the ajax flag the handler reads.
 */
final class FakeRequest
{
    public bool $ajax = false;

    /** @param array<string, string> $headers */
    public function __construct(private readonly array $headers = [])
    {
    }

    public function getHeader(string $header, string $default = ''): string
    {
        return $this->headers[$header] ?? $default;
    }
}

/**
 * Minimal Throwable carrying an HTTP status for the typed-exception path.
 */
final class HttpStatusException extends \Exception
{
    public function __construct(private readonly int $httpStatus)
    {
        parent::__construct('boom');
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}