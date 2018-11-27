<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 *
 * $cacheFile = 'google_merchant_'.$oShop->id.'.xml';
 * if (KovSpace_Cache::check($cacheFile, 60)) {
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

    public static function check ($filename, $lifetime) {

        $filepath = self::$cacheDir . $filename;

        if (file_exists($filepath) && (time() - @filemtime($filepath)) < $lifetime) {
            $content = file_get_contents($filepath);
            echo $content;
            return false;
        } else {
            ob_start();
            return true;
        }
    }
    public static function save ($filename) {
        $filepath = self::$cacheDir . $filename;
        $content = ob_get_contents();
        ob_end_clean();
        file_put_contents($filepath, $content);
        echo $content;
    }
}