<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Imgorientation
{
    /**
     * Apply JPEG EXIF orientation to GD image resource.
     */
    public static function applyExifToResource(GdImage $resource, string $path): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $resource;
        }

        $imageType = @exif_imagetype($path);
        if ($imageType !== IMAGETYPE_JPEG) {
            return $resource;
        }

        $aEXIF = @exif_read_data($path, 'IFD0');
        if (!isset($aEXIF['Orientation'])) {
            return $resource;
        }

        switch ((int) $aEXIF['Orientation']) {
            case 3:
                $resource = imagerotate($resource, 180, 0);
                break;
            case 6:
                $resource = imagerotate($resource, -90, 0);
                break;
            case 8:
                $resource = imagerotate($resource, 90, 0);
                break;
        }

        return $resource;
    }

    /**
     * Load image from file and apply EXIF orientation for JPEG.
     */
    public static function loadImage(string $path, ?string $ext = null): ?GdImage
    {
        if (!is_readable($path)) {
            return null;
        }

        $ext = strtolower($ext ?? Core_File::getExtension($path));

        $resource = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'gif' => @imagecreatefromgif($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };

        if (!$resource instanceof GdImage) {
            return null;
        }

        if ($ext === 'png') {
            imagepalettetotruecolor($resource);
        }

        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            $resource = self::applyExifToResource($resource, $path);
        }

        return $resource;
    }

    /**
     * Rotate image file in place.
     */
    public static function rotateFile(string $path, int $angle): bool
    {
        $resource = self::loadImage($path);
        if (!$resource) {
            return false;
        }

        $rotated = imagerotate($resource, $angle, 0);
        imagedestroy($resource);

        if (!$rotated) {
            return false;
        }

        $ext = strtolower(Core_File::getExtension($path));
        $saved = match ($ext) {
            'webp' => imagewebp($rotated, $path),
            'jpg', 'jpeg' => imagejpeg($rotated, $path, 90),
            'png' => imagepng($rotated, $path),
            'gif' => imagegif($rotated, $path),
            default => false,
        };

        imagedestroy($rotated);

        return (bool) $saved;
    }
}
