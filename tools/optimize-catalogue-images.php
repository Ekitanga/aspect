<?php
/**
 * Convert the generated catalogue PNG masters to delivery-ready WebP assets.
 *
 * Usage:
 *   C:\xampp\php\php.exe tools\optimize-catalogue-images.php
 */

declare(strict_types=1);

$catalogue_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'catalogue';

if (!extension_loaded('gd') || !function_exists('imagewebp')) {
    fwrite(STDERR, "GD with WebP support is required.\n");
    exit(1);
}

$files = glob($catalogue_dir . DIRECTORY_SEPARATOR . '*.png') ?: [];

if (count($files) !== 54) {
    fwrite(STDERR, sprintf("Expected 54 PNG masters (featured and gallery), found %d.\n", count($files)));
    exit(1);
}

$written = 0;
$source_bytes = 0;
$output_bytes = 0;

foreach ($files as $source_path) {
    $source = imagecreatefrompng($source_path);
    if (!$source) {
        fwrite(STDERR, "Unable to read {$source_path}.\n");
        exit(1);
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $target_size = min(1200, $width, $height);
    $target = imagecreatetruecolor($target_size, $target_size);

    if (!$target) {
        imagedestroy($source);
        fwrite(STDERR, "Unable to allocate the target image.\n");
        exit(1);
    }

    imagealphablending($target, true);
    $background = imagecolorallocate($target, 252, 248, 245);
    imagefill($target, 0, 0, $background);
    imagecopyresampled($target, $source, 0, 0, 0, 0, $target_size, $target_size, $width, $height);

    $output_path = preg_replace('/\.png$/i', '.webp', $source_path);
    if (!$output_path || !imagewebp($target, $output_path, 88)) {
        imagedestroy($target);
        imagedestroy($source);
        fwrite(STDERR, "Unable to write WebP for {$source_path}.\n");
        exit(1);
    }

    imagedestroy($target);
    imagedestroy($source);

    $source_bytes += (int) filesize($source_path);
    $output_bytes += (int) filesize($output_path);
    ++$written;
}

printf(
    "Created %d WebP files (%.1f MB -> %.1f MB).\n",
    $written,
    $source_bytes / 1048576,
    $output_bytes / 1048576
);
