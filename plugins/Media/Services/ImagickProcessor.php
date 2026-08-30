<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Services;

class ImagickProcessor implements ImageProcessorInterface
{
    private \Imagick $image;

    public function load(string $path): static
    {
        $this->image = new \Imagick($path);
        return $this;
    }

    public function resize(int $width): static
    {
        $origWidth  = $this->image->getImageWidth();
        $origHeight = $this->image->getImageHeight();

        if ($origWidth <= $width) {
            return $this;
        }

        $height = (int) round($origHeight * ($width / $origWidth));
        $this->image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);

        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height): static
    {
        $this->image->cropImage($width, $height, $x, $y);
        $this->image->setImagePage(0, 0, 0, 0);
        return $this;
    }

    public function rotate(int $degrees): static
    {
        $this->image->rotateImage(new \ImagickPixel('transparent'), (float) $degrees);
        return $this;
    }

    public function flip(string $direction): static
    {
        if ($direction === 'horizontal') {
            $this->image->flipImage();
        } else {
            $this->image->flopImage();
        }
        return $this;
    }

    public function sharpen(): static
    {
        $this->image->unsharpMaskImage(0, 0.5, 1, 0.05);
        return $this;
    }

    public function brightness(int $level): static
    {
        if ($level === 0) {
            return $this;
        }
        $this->image->modulateImage(100 + $level, 100, 100);
        return $this;
    }

    public function contrast(int $level): static
    {
        if ($level === 0) {
            return $this;
        }
        $sharpen  = $level > 0;
        $strength = abs($level) / 10.0;
        $range    = \Imagick::getQuantumRange();
        $midpoint = $range['quantumRangeLong'] * 0.5;

        $this->image->sigmoidalContrastImage($sharpen, $strength, $midpoint);
        return $this;
    }

    public function autoOrient(): static
    {
        $this->image->autoOrient();
        return $this;
    }

    public function stripExif(): static
    {
        $this->image->stripImage();
        return $this;
    }

    public function toWebp(string $outputPath, int $quality = 85): void
    {
        $this->image->setImageFormat('webp');
        $this->image->setImageCompressionQuality($quality);
        $this->image->writeImage($outputPath);
        $this->image->clear();
    }

    public function save(string $outputPath, ?int $quality = null): void
    {
        $ext = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
        $format = match ($ext) {
            'jpg', 'jpeg' => 'jpeg',
            'png'         => 'png',
            'gif'         => 'gif',
            'webp'        => 'webp',
            default       => 'jpeg',
        };

        $this->image->setImageFormat($format);

        if ($format === 'png') {
            $this->image->setImageCompressionQuality(95);
        } else {
            $q = $quality ?? match ($format) {
                'jpeg' => 92,
                'webp' => 85,
                default => 85,
            };
            $this->image->setImageCompressionQuality($q);
        }

        $this->image->writeImage($outputPath);
        $this->image->clear();
    }

    public function getInfo(string $path): array
    {
        $img  = new \Imagick($path);
        $info = [
            'width'  => $img->getImageWidth(),
            'height' => $img->getImageHeight(),
            'mime'   => $img->getImageMimeType(),
        ];
        $img->clear();

        return $info;
    }

    public function getExif(string $path): array
    {
        $img   = new \Imagick($path);
        $props = $img->getImageProperties('exif:*');
        $img->clear();

        $skip   = ['MakerNote', 'ComponentsConfiguration', 'FileSource', 'SceneType', 'PrintIM'];
        $result = [];

        foreach ($props as $key => $value) {
            $cleanKey = preg_replace('/^exif:/', '', $key);

            if (in_array($cleanKey, $skip, true)) {
                continue;
            }
            if (str_starts_with($cleanKey, 'UndefinedTag:')) {
                continue;
            }
            if (strlen($value) > 500 || !mb_check_encoding($value, 'UTF-8')) {
                continue;
            }

            $result[$cleanKey] = $value;
        }

        ksort($result);
        return $result;
    }

    public function capabilities(): array
    {
        return ['crop', 'rotate', 'flip', 'resize', 'sharpen', 'brightness', 'contrast', 'auto_orient', 'strip_exif'];
    }
}
