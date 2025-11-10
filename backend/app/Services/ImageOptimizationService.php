<?php

namespace App\Services;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    /**
     * Image sizes configuration
     */
    private array $sizes = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 400, 'height' => 300],
        'medium' => ['width' => 800, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 900],
        'original' => null,
    ];

    /**
     * Optimize and upload image
     */
    public function uploadAndOptimize($file, string $directory = 'media'): array
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $results = [];

        foreach ($this->sizes as $sizeName => $dimensions) {
            $path = "{$directory}/{$sizeName}/{$filename}";

            if ($sizeName === 'original') {
                // Store original without processing
                Storage::disk('public')->put($path, file_get_contents($file));
            } else {
                // Process and optimize image
                $image = Image::make($file);

                // Resize maintaining aspect ratio
                $image->resize($dimensions['width'], $dimensions['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Optimize quality
                $quality = $this->getQualityForSize($sizeName);
                $optimized = $image->encode($file->getClientOriginalExtension(), $quality);

                Storage::disk('public')->put($path, $optimized);
            }

            $results[$sizeName] = [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'size' => Storage::disk('public')->size($path),
            ];
        }

        return $results;
    }

    /**
     * Create responsive image variants
     */
    public function createResponsiveVariants(string $imagePath): array
    {
        $image = Image::make(Storage::disk('public')->get($imagePath));
        $info = pathinfo($imagePath);
        $results = [];

        $widths = [320, 640, 768, 1024, 1280, 1920];

        foreach ($widths as $width) {
            $variantPath = "{$info['dirname']}/{$info['filename']}-{$width}w.{$info['extension']}";

            $variant = clone $image;
            $variant->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            Storage::disk('public')->put(
                $variantPath,
                $variant->encode($info['extension'], 85)
            );

            $results[$width] = [
                'path' => $variantPath,
                'url' => Storage::disk('public')->url($variantPath),
            ];
        }

        return $results;
    }

    /**
     * Convert image to WebP format
     */
    public function convertToWebP(string $imagePath): string
    {
        $image = Image::make(Storage::disk('public')->get($imagePath));
        $info = pathinfo($imagePath);

        $webpPath = "{$info['dirname']}/{$info['filename']}.webp";
        $webpImage = $image->encode('webp', 85);

        Storage::disk('public')->put($webpPath, $webpImage);

        return $webpPath;
    }

    /**
     * Apply watermark to image
     */
    public function applyWatermark(string $imagePath, string $watermarkPath, string $position = 'bottom-right'): void
    {
        $image = Image::make(Storage::disk('public')->get($imagePath));
        $watermark = Image::make(Storage::disk('public')->get($watermarkPath));

        // Resize watermark to 20% of image width
        $watermarkWidth = $image->width() * 0.2;
        $watermark->resize($watermarkWidth, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        // Apply watermark based on position
        switch ($position) {
            case 'top-left':
                $image->insert($watermark, 'top-left', 10, 10);
                break;
            case 'top-right':
                $image->insert($watermark, 'top-right', 10, 10);
                break;
            case 'bottom-left':
                $image->insert($watermark, 'bottom-left', 10, 10);
                break;
            case 'bottom-right':
            default:
                $image->insert($watermark, 'bottom-right', 10, 10);
                break;
        }

        Storage::disk('public')->put($imagePath, $image->encode());
    }

    /**
     * Crop image to specific aspect ratio
     */
    public function cropToAspectRatio(string $imagePath, string $ratio = '16:9'): string
    {
        $image = Image::make(Storage::disk('public')->get($imagePath));
        $info = pathinfo($imagePath);

        [$ratioWidth, $ratioHeight] = explode(':', $ratio);
        $targetRatio = $ratioWidth / $ratioHeight;

        $width = $image->width();
        $height = $image->height();
        $currentRatio = $width / $height;

        if ($currentRatio > $targetRatio) {
            // Image is too wide
            $newWidth = $height * $targetRatio;
            $x = ($width - $newWidth) / 2;
            $image->crop($newWidth, $height, $x, 0);
        } else {
            // Image is too tall
            $newHeight = $width / $targetRatio;
            $y = ($height - $newHeight) / 2;
            $image->crop($width, $newHeight, 0, $y);
        }

        $croppedPath = "{$info['dirname']}/{$info['filename']}-{$ratio}.{$info['extension']}";
        Storage::disk('public')->put($croppedPath, $image->encode());

        return $croppedPath;
    }

    /**
     * Delete image and all its variants
     */
    public function deleteImage(string $basePath): void
    {
        $info = pathinfo($basePath);

        foreach ($this->sizes as $sizeName => $dimensions) {
            $path = "{$info['dirname']}/{$sizeName}/{$info['basename']}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // Delete WebP version
        $webpPath = "{$info['dirname']}/{$info['filename']}.webp";
        if (Storage::disk('public')->exists($webpPath)) {
            Storage::disk('public')->delete($webpPath);
        }
    }

    /**
     * Get quality setting based on size
     */
    private function getQualityForSize(string $sizeName): int
    {
        return match ($sizeName) {
            'thumbnail' => 60,
            'small' => 70,
            'medium' => 80,
            'large' => 85,
            default => 90,
        };
    }
}
