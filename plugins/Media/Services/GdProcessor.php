<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Services;

class GdProcessor implements ImageProcessorInterface
{
    private ?\GdImage $image = null;
    private string $mime;
    private string $loadedPath;

    /**
     * The loaded image, or a LogicException-safe failure for call-order bugs.
     *
     * Every edit method requires load() to have run; without this guard the
     * failure would surface as an opaque TypeError inside the GD extension.
     */
    private function requireImage(): \GdImage
    {
        if ($this->image === null) {
            throw new \LogicException('No image loaded. Call load() first.');
        }
        return $this->image;
    }

    public function load(string $path): static
    {
        $info = getimagesize($path);
        if ($info === false) {
            throw new \InvalidArgumentException("Unable to read image: {$path}");
        }
        $this->mime       = $info['mime'];
        $this->loadedPath = $path;

        $image = match ($this->mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/gif'  => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => throw new \InvalidArgumentException("Unsupported image type: {$this->mime}"),
        };
        if ($image === false) {
            throw new \InvalidArgumentException("Unable to decode image: {$path}");
        }
        $this->image = $image;

        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);

        return $this;
    }

    public function resize(int $width): static
    {
        if ($width < 1) {
            throw new \InvalidArgumentException("Resize width must be at least 1, got {$width}");
        }
        $image      = $this->requireImage();
        $origWidth  = imagesx($image);
        $origHeight = imagesy($image);

        if ($origWidth <= $width) {
            return $this;
        }

        $height  = max(1, (int) round($origHeight * ($width / $origWidth)));
        $resized = imagecreatetruecolor($width, $height);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        imagedestroy($image);
        $this->image = $resized;

        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height): static
    {
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException("Crop dimensions must be at least 1, got {$width}x{$height}");
        }
        $image   = $this->requireImage();
        $cropped = imagecreatetruecolor($width, $height);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagecopy($cropped, $image, 0, 0, $x, $y, $width, $height);

        imagedestroy($image);
        $this->image = $cropped;

        return $this;
    }

    public function rotate(int $degrees): static
    {
        $image   = $this->requireImage();
        $rotated = imagerotate($image, -$degrees, 0);
        if ($rotated === false) {
            return $this;
        }
        imagealphablending($rotated, true);
        imagesavealpha($rotated, true);

        imagedestroy($image);
        $this->image = $rotated;

        return $this;
    }

    public function flip(string $direction): static
    {
        $mode = ($direction === 'horizontal') ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL;
        imageflip($this->requireImage(), $mode);
        return $this;
    }

    public function sharpen(): static
    {
        $matrix = [
            [0, -1, 0],
            [-1, 5, -1],
            [0, -1, 0],
        ];
        imageconvolution($this->requireImage(), $matrix, 1, 0);
        return $this;
    }

    public function brightness(int $level): static
    {
        if ($level === 0) {
            return $this;
        }
        $gdLevel = (int) round($level * 2.55);
        imagefilter($this->requireImage(), IMG_FILTER_BRIGHTNESS, $gdLevel);
        return $this;
    }

    public function contrast(int $level): static
    {
        if ($level === 0) {
            return $this;
        }
        imagefilter($this->requireImage(), IMG_FILTER_CONTRAST, -$level);
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

        $image = $this->requireImage();

        switch ($exif['Orientation']) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $this->replaceImage(imagerotate($image, 180, 0));
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $this->replaceImage(imagerotate($image, -90, 0));
                imageflip($this->requireImage(), IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $this->replaceImage(imagerotate($image, -90, 0));
                break;
            case 7:
                $this->replaceImage(imagerotate($image, 90, 0));
                imageflip($this->requireImage(), IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $this->replaceImage(imagerotate($image, 90, 0));
                break;
        }

        return $this;
    }

    /**
     * Swap in a rotated image, destroying the previous one. Keeps the
     * current image untouched when GD fails to rotate.
     */
    private function replaceImage(\GdImage|false $newImage): void
    {
        if ($newImage === false) {
            return;
        }
        $old = $this->requireImage();
        imagealphablending($newImage, true);
        imagesavealpha($newImage, true);
        imagedestroy($old);
        $this->image = $newImage;
    }

    public function stripExif(): static
    {
        return $this;
    }

    public function toWebp(string $outputPath, int $quality = 85): void
    {
        $image = $this->requireImage();
        imagewebp($image, $outputPath, $quality);
        imagedestroy($image);
        $this->image = null;
    }

    public function save(string $outputPath, ?int $quality = null): void
    {
        $image = $this->requireImage();
        $ext = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $outputPath, $quality ?? 92),
            'png'         => imagepng($image, $outputPath, 9),
            'gif'         => imagegif($image, $outputPath),
            'webp'        => imagewebp($image, $outputPath, $quality ?? 85),
            default       => imagejpeg($image, $outputPath, $quality ?? 92),
        };

        imagedestroy($image);
        $this->image = null;
    }

    /**
     * @return array{width: int, height: int, mime: string}
     */
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

    /**
     * @return array<string, string> Cleaned EXIF key => value pairs
     */
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

    /**
     * @return list<string> Supported edit operation names
     */
    public function capabilities(): array
    {
        $caps = ['crop', 'rotate', 'flip', 'resize', 'sharpen', 'brightness', 'contrast', 'strip_exif'];

        if (function_exists('exif_read_data')) {
            $caps[] = 'auto_orient';
        }

        return $caps;
    }
}
