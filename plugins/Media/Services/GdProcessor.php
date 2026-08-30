<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Services;

class GdProcessor implements ImageProcessorInterface
{
    private ?\GdImage $image = null;
    private string $mime;
    private string $loadedPath;

    public function load(string $path): static
    {
        $info = getimagesize($path);
        if ($info === false) {
            throw new \InvalidArgumentException("Unable to read image: {$path}");
        }
        $this->mime       = $info['mime'];
        $this->loadedPath = $path;

        $this->image = match ($this->mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/gif'  => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => throw new \InvalidArgumentException("Unsupported image type: {$this->mime}"),
        };

        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);

        return $this;
    }

    public function resize(int $width): static
    {
        $origWidth  = imagesx($this->image);
        $origHeight = imagesy($this->image);

        if ($origWidth <= $width) {
            return $this;
        }

        $height  = (int) round($origHeight * ($width / $origWidth));
        $resized = imagecreatetruecolor($width, $height);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        imagedestroy($this->image);
        $this->image = $resized;

        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height): static
    {
        $cropped = imagecreatetruecolor($width, $height);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagecopy($cropped, $this->image, 0, 0, $x, $y, $width, $height);

        imagedestroy($this->image);
        $this->image = $cropped;

        return $this;
    }

    public function rotate(int $degrees): static
    {
        $rotated = imagerotate($this->image, -$degrees, 0);
        imagealphablending($rotated, true);
        imagesavealpha($rotated, true);

        imagedestroy($this->image);
        $this->image = $rotated;

        return $this;
    }

    public function flip(string $direction): static
    {
        $mode = ($direction === 'horizontal') ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL;
        imageflip($this->image, $mode);
        return $this;
    }

    public function sharpen(): static
    {
        $matrix = [
            [0, -1, 0],
            [-1, 5, -1],
            [0, -1, 0],
        ];
        imageconvolution($this->image, $matrix, 1, 0);
        return $this;
    }

    public function brightness(int $level): static
    {
        if ($level === 0) {
            return $this;
        }
        $gdLevel = (int) round($level * 2.55);
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $gdLevel);
        return $this;
    }

    public function contrast(int $level): static
    {
        if ($level === 0) {
            return $this;
        }
        imagefilter($this->image, IMG_FILTER_CONTRAST, -$level);
        return $this;
    }

    public function autoOrient(): static
    {
        if (!function_exists('exif_read_data')) {
            return $this;
        }

        $exif = @exif_read_data($this->loadedPath);
        if ($exif === false || !isset($exif['Orientation'])) {
            return $this;
        }

        switch ($exif['Orientation']) {
            case 2:
                imageflip($this->image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $rotated = imagerotate($this->image, 180, 0);
                imagedestroy($this->image);
                $this->image = $rotated;
                break;
            case 4:
                imageflip($this->image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $rotated = imagerotate($this->image, -90, 0);
                imagedestroy($this->image);
                $this->image = $rotated;
                imageflip($this->image, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $rotated = imagerotate($this->image, -90, 0);
                imagedestroy($this->image);
                $this->image = $rotated;
                break;
            case 7:
                $rotated = imagerotate($this->image, 90, 0);
                imagedestroy($this->image);
                $this->image = $rotated;
                imageflip($this->image, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $rotated = imagerotate($this->image, 90, 0);
                imagedestroy($this->image);
                $this->image = $rotated;
                break;
        }

        return $this;
    }

    public function stripExif(): static
    {
        return $this;
    }

    public function toWebp(string $outputPath, int $quality = 85): void
    {
        imagewebp($this->image, $outputPath, $quality);
        imagedestroy($this->image);
        $this->image = null;
    }

    public function save(string $outputPath, ?int $quality = null): void
    {
        $ext = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($this->image, $outputPath, $quality ?? 92),
            'png'         => imagepng($this->image, $outputPath, 9),
            'gif'         => imagegif($this->image, $outputPath),
            'webp'        => imagewebp($this->image, $outputPath, $quality ?? 85),
            default       => imagejpeg($this->image, $outputPath, $quality ?? 92),
        };

        imagedestroy($this->image);
        $this->image = null;
    }

    public function getInfo(string $path): array
    {
        $info = getimagesize($path);
        if ($info === false) {
            throw new \InvalidArgumentException("Unable to read image: {$path}");
        }

        return [
            'width'  => $info[0],
            'height' => $info[1],
            'mime'   => $info['mime'],
        ];
    }

    public function getExif(string $path): array
    {
        if (!function_exists('exif_read_data')) {
            return [];
        }

        $mime = mime_content_type($path);
        if (!in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
            return [];
        }

        $exif = @exif_read_data($path, null, true);
        if ($exif === false) {
            return [];
        }

        $skipSections = ['FILE', 'THUMBNAIL'];
        $skipKeys     = ['MakerNote', 'ComponentsConfiguration', 'FileSource', 'SceneType', 'PrintIM',
                         'SectionsFound', 'IsColor', 'ByteOrderMotorola'];
        $result       = [];

        foreach ($exif as $section => $data) {
            if (!is_array($data)) {
                continue;
            }
            if (in_array($section, $skipSections, true)) {
                continue;
            }

            foreach ($data as $key => $value) {
                if (in_array($key, $skipKeys, true)) {
                    continue;
                }
                    if (str_starts_with((string) $key, 'UndefinedTag:')) {
                    continue;
                }

                if (is_array($value)) {
                    $value = implode(', ', array_map('strval', $value));
                }
                $value = (string) $value;

                if (strlen($value) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
                    continue;
                }

                $result[$key] = $value;
            }
        }

        ksort($result);
        return $result;
    }

    public function capabilities(): array
    {
        $caps = ['crop', 'rotate', 'flip', 'resize', 'sharpen', 'brightness', 'contrast', 'strip_exif'];

        if (function_exists('exif_read_data')) {
            $caps[] = 'auto_orient';
        }

        return $caps;
    }
}
