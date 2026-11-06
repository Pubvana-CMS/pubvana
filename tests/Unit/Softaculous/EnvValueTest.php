<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Softaculous;

use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Softaculous installer writes .env by hand, so every value has to be
 * encoded the way phpdotenv reads it back. A password with a space, #, $ or
 * a quote used to load wrong, which then failed the install with a database
 * error that pointed at nothing useful.
 */
final class EnvValueTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('SOFTACULOUS')) {
            define('SOFTACULOUS', true);
        }

        require_once dirname(__DIR__, 3) . '/softaculous/install.php';
    }

    public function testPlainValuesStayBare(): void
    {
        self::assertSame('localhost', __envValue('localhost'));
        self::assertSame('my_db', __envValue('my_db'));
        self::assertSame('https://example.com:8443/pub', __envValue('https://example.com:8443/pub'));
    }

    public function testValuesWithSpacesAreQuoted(): void
    {
        self::assertSame('"my site"', __envValue('my site'));
    }

    public function testEmptyValueIsQuoted(): void
    {
        self::assertSame('""', __envValue(''));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function trickyValues(): array
    {
        return [
            'space' => ['pa ss'],
            'hash' => ['pa#ss'],
            'dollar' => ['pa$ss'],
            'double quote' => ['pa"ss'],
            'single quote' => ["pa'ss"],
            'backslash' => ['pa\\ss'],
            'leading and trailing space' => ['  padded  '],
            'interpolation' => ['${HOME}'],
            'newline' => ["line\nbreak"],
        ];
    }

    #[DataProvider('trickyValues')]
    public function testDotenvReadsBackTheOriginalValue(string $value): void
    {
        $parsed = Dotenv::parse('TEST_VALUE=' . __envValue($value) . "\n");

        self::assertSame($value, $parsed['TEST_VALUE'] ?? null);
    }
}
