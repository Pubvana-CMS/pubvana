<?php

declare(strict_types=1);

namespace Pubvana\Tests\Support;

/**
 * Minimal zip writer for tests that need explicit Unix mode bits.
 *
 * PHP's ZipArchive cannot set an entry's external attributes, so an archive
 * carrying a symlink entry (mode 0120777) cannot be produced through the
 * extension API. The bytes are written directly instead: a stored (method 0)
 * local file header per entry, a central directory, and the end record.
 * Enough of the format that ZipArchive opens the result and reports the
 * attributes through getExternalAttributesIndex().
 *
 * @package Pubvana\Tests\Support
 */
final class ZipFactory
{
    /**
     * Write a zip whose entries carry the given Unix modes.
     *
     * @param string $path    Destination zip path.
     * @param list<array{name: string, content: string, mode: int}> $entries
     */
    public static function write(string $path, array $entries): void
    {
        $local   = '';
        $central = '';
        $offset  = 0;

        foreach ($entries as $entry) {
            $name    = $entry['name'];
            $content = $entry['content'];
            $size    = strlen($content);
            $nameLen = strlen($name);
            $crc     = self::unsignedCrc32($content);

            $local .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLen, 0)
                . $name . $content;

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                (3 << 8) | 20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLen,
                0,
                0,
                0,
                0,
                $entry['mode'] << 16,
                $offset
            ) . $name;

            $offset += 30 + $nameLen + $size;
        }

        $count = count($entries);
        $eocd  = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($local), 0);

        file_put_contents($path, $local . $central . $eocd);
    }

    /**
     * CRC-32 as an unsigned 32-bit value (pack V needs 0..4294967295).
     */
    private static function unsignedCrc32(string $data): int
    {
        $crc = crc32($data);

        return $crc < 0 ? $crc + 4294967296 : $crc;
    }
}
