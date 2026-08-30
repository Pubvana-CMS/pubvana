<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Services;

class VideoThumbnailService
{
    public function isAvailable(): bool
    {
        $result = @exec('which ffmpeg 2>/dev/null', $output, $code);
        return $code === 0 && !empty($result);
    }

    public function extract(string $videoPath, string $outputPath): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $videoPath  = escapeshellarg($videoPath);
        $outputPath = escapeshellarg($outputPath);

        $cmd = "ffmpeg -i {$videoPath} -ss 00:00:01 -vframes 1 -f image2 {$outputPath} 2>/dev/null";
        @exec($cmd, $output, $code);

        return $code === 0;
    }
}
