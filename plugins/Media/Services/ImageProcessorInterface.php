<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Services;

interface ImageProcessorInterface
{
    public function load(string $path): static;

    public function resize(int $width): static;

    public function crop(int $x, int $y, int $width, int $height): static;

    public function rotate(int $degrees): static;

    public function flip(string $direction): static;

    public function sharpen(): static;

    public function brightness(int $level): static;

    public function contrast(int $level): static;

    public function autoOrient(): static;

    public function stripExif(): static;

    public function toWebp(string $outputPath, int $quality = 85): void;

    public function save(string $outputPath, ?int $quality = null): void;

    public function getInfo(string $path): array;

    public function getExif(string $path): array;

    public function capabilities(): array;
}
