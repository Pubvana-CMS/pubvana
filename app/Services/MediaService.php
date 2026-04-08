<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;

class MediaService
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_SIZE_KB  = 10240; // 10 MB

    private const SIZES = [
        'thumbnail' => [300, 200],
        'medium'    => [800, 600],
    ];

    public function upload(UploadedFile $file, int $uploadedBy = 0, string $altText = '', string $title = ''): array
    {
        if (! $file->isValid() || $file->hasMoved()) {
            throw new \RuntimeException('Invalid or already-moved upload.');
        }
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Only JPEG, PNG, WebP, and GIF images are accepted.');
        }
        if ($file->getSizeByUnit('kb') > self::MAX_SIZE_KB) {
            throw new \RuntimeException('Image must be 10 MB or smaller.');
        }

        // Capture these before move() — temp file is gone afterwards
        $mimeType       = $file->getMimeType();
        $fileSize       = $file->getSize();
        $origName       = $file->getName();
        $convertToWebP  = in_array($mimeType, ['image/jpeg', 'image/png'], true);

        $ext     = $this->mimeToExt($mimeType);
        $name    = bin2hex(random_bytes(16));
        $relDir  = 'uploads/' . date('Y/m');
        $absDir  = FCPATH . $relDir;

        if (! is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $tmpPath = WRITEPATH . 'tmp/' . 'tmp_' . $name . '.' . $ext;
        $file->move(WRITEPATH . 'tmp/', 'tmp_' . $name . '.' . $ext);

        // CI4's GD handler always encodes in the source format, so we let it
        // write to original-extension intermediates, then convert to WebP ourselves.
        $absIntermediate      = FCPATH . $relDir . '/' . $name . '.' . $ext;
        $thumbDir             = FCPATH . $relDir . '/thumbs';
        $thumbIntermediate    = $thumbDir . '/' . $name . '.' . $ext;

        if (! is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        Services::image('gd')
            ->withFile($tmpPath)
            ->resize(1920, 1200, true, 'width')
            ->save($absIntermediate, 85);

        Services::image('gd')
            ->withFile($tmpPath)
            ->fit(300, 200, 'center')
            ->save($thumbIntermediate, 80);

        if ($convertToWebP) {
            $relPath   = '/' . $relDir . '/' . $name . '.webp';
            $absPath   = FCPATH . $relDir . '/' . $name . '.webp';
            $thumbPath = $thumbDir . '/' . $name . '.webp';
            $mimeType  = 'image/webp';
            $this->saveAsWebP($absIntermediate, $absPath, 85);
            $this->saveAsWebP($thumbIntermediate, $thumbPath, 80);
            @unlink($absIntermediate);
            @unlink($thumbIntermediate);
        } else {
            $relPath   = '/' . $relDir . '/' . $name . '.' . $ext;
            $absPath   = $absIntermediate;
            $thumbPath = $thumbIntermediate;
        }

        @unlink($tmpPath);

        $model   = new \App\Models\MediaModel();
        $mediaId = $model->insert([
            'filename'    => $origName,
            'path'        => $relPath,
            'mime_type'   => $mimeType,
            'size'        => $fileSize,
            'alt_text'    => $altText,
            'title'       => $title,
            'uploaded_by' => $uploadedBy,
        ]);

        $thumbPath = $this->deriveThumbPath($relPath);

        return [
            'id'        => $mediaId,
            'filename'  => $origName,
            'path'      => $relPath,
            'url'       => base_url($relPath),
            'mime_type' => $mimeType,
            'alt_text'  => $altText,
            'title'     => $title,
            'thumb_path' => $thumbPath,
        ];
    }

    public function delete(int $id): bool
    {
        $mediaModel = model(\App\Models\MediaModel::class);
        $media = $mediaModel->find($id);
        if (! $media) {
            return false;
        }
        $abs = FCPATH . ltrim($media->path, '/');
        if (is_file($abs)) {
            @unlink($abs);
        }
        $thumbPath = $this->deriveThumbPath($media->path);
        $absThumb  = FCPATH . ltrim($thumbPath, '/');
        if (is_file($absThumb)) {
            @unlink($absThumb);
        }
        $mediaModel->delete($id);
        return true;
    }

    /**
     * Derives the thumbnail path from a full media path by inserting /thumbs/
     * before the filename. E.g. /uploads/2026/02/file.webp → /uploads/2026/02/thumbs/file.webp
     */
    private function deriveThumbPath(string $path): string
    {
        $dir      = dirname($path);
        $filename = basename($path);
        return $dir . '/thumbs/' . $filename;
    }

    private function saveAsWebP(string $src, string $dest, int $quality): void
    {
        $mime = mime_content_type($src);
        $img  = match ($mime) {
            'image/png'  => imagecreatefrompng($src),
            default      => imagecreatefromjpeg($src),
        };
        if ($mime === 'image/png') {
            // Preserve PNG transparency
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }
        imagewebp($img, $dest, $quality);
        imagedestroy($img);
    }

    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
    }
}
