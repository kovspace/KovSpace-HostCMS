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
 * @version 2018-11-27
 * @copyright © 2018 https://kovspace.com/
 */

class KovSpace_Cache
{
    protected static $cacheDir = CMS_FOLDER . 'hostcmsfiles/cache/';
    protected static $clearFile = CMS_FOLDER . 'hostcmsfiles/cache/_clear.txt';

    /* Exclusion rules */
    public static function is_cache_deny() {
        if (Core_Auth::logged()) {
            return true;
        }
        return false;
    }

    /* Check file exists and start buffering */
    public static function check($filename, $lifetime = 3600) {
        if (self::is_cache_deny() || !$filename) return true;

        // Clear old files (once a day = 86400 sec)
        if (!is_file(self::$clearFile) || (time() - @filemtime(self::$clearFile)) > 86400) {
            self::clear();
        }

        $filepath = self::$cacheDir . $filename;
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
    public static function save($filename) {
        if (self::is_cache_deny() || !$filename) return;
        $filepath = self::$cacheDir . $filename;
        $content = ob_get_contents();
        ob_end_clean();
        file_put_contents($filepath, $content);
        echo $content;
    }

    /* Remove all cache files */
    public static function clear() {
        $dir = self::$cacheDir;
        $files = glob($dir);
        foreach($files as $file){
            if (is_file($file)) {
                unlink($file);
            }
        }
        $content = date("Y-m-d H:i:s");
        file_put_contents(self::$clearFile, $content);
    }
}