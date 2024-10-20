<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 *
 * if (KovSpace_Cache::check($cacheFile = 'google_merchant_'.$oShop->id.'.xml', 60)) {
 *      # Your code here...
 *      KovSpace_Cache::save($cacheFile);
 * }
 *
 * Cache wrapper
 *
 * @author KovSpace
 * @version 2024-10-18
 * @copyright © 2024 https://kovspace.com/
 */

class KovSpace_Cache
{
    public static string $dir = CMS_FOLDER . 'hostcmsfiles/cache/';
    public static string $clear = CMS_FOLDER . 'hostcmsfiles/cache/.clear';
    public static string $lock = CMS_FOLDER . 'hostcmsfiles/cache/.lock';

    /* Exclusion rules */
    public static function is_cache_deny(): ?bool
    {
        return Core_Auth::logged();
    }

    /* Check file exists and start buffering */
    public static function check(?string $filename, int $lifetime = 3600, bool $check = true): bool
    {
        if (self::is_cache_deny() || !$filename) {
            return true;
        }

        if (!$check) {
            return true;
        }

        // Clear old files (once a day = 86400 sec)
        if (!is_file(self::$clear) || (time() - @filemtime(self::$clear)) > 86400) {
            self::clear();
        }

        $filepath = self::$dir . $filename;
        if (is_file($filepath) && (time() - @filemtime($filepath)) < $lifetime) {
            $content = file_get_contents($filepath);
            echo $content;
            return false;
        } else {
            ob_start();
            return true;
        }
    }

    /* End buffering and save file */
    public static function save(?string $filename, bool $save = true): void
    {
        if (self::is_cache_deny() || !$filename) {
            return;
        }

        if (!$save) {
            return;
        }

        $filepath = self::$dir . $filename;
        $content = ob_get_contents();
        ob_end_clean();
        file_put_contents($filepath, $content);
        echo $content;
    }

    /* Remove all cache files */
    public static function clear(): void
    {
        $lock = self::$lock;
        if (is_file($lock) && (time() - @filemtime(self::$clear)) > 10000) {
            unlink($lock);
        }
        if (!is_file($lock)) {
            fopen($lock, 'w');
            $dir = self::$dir;
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $content = date('Y-m-d H:i:s');
            file_put_contents(self::$clear, $content);
            unlink($lock);
        }
    }
}
