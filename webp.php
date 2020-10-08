<?php

/* PNG to WEBP */
/* Shop and Information System main images */

require_once(dirname(__FILE__) . '/../../' . 'bootstrap.php');

function convert($oItem) {
    $image = $oItem->image_large;
    $size = 'large';
    $dir = $oItem->getItemPath();
    webp($dir, $image, $size);

    $image = $oItem->image_small;
    $size = 'small';
    $dir = $oItem->getItemPath();
    webp($dir, $image, $size);
}

function webp($dir, $image, $size) {
    if ($image) {
        $path = $dir . $image;
        {
            if (file_exists($path)) {
                $dotpos = strrpos($image, '.');
                $name = substr($image, 0, $dotpos);
                $ext = substr($image, $dotpos);
                $newname = $name . '.webp';
                $newpath = $dir . $newname;

                if ($ext == '.png') {
                    echo $path . PHP_EOL;
                    $im = @imagecreatefrompng($path);
                    if ($im) {

                        imagepalettetotruecolor($im); // for png
                        imagewebp($im, $newpath);
                        imagedestroy($im);

                        if ($size == 'large') {
                            $oItem->image_large = $newname;
                        }

                        if ($size == 'small') {
                            $oItem->image_small = $newname;
                        }

                        if (file_exists($newpath)) {
                            $oItem->save();
                            unlink($path);
                        }

                    } else {
                        echo 'Error: ' . $path . PHP_EOL;
                    }
                }
            }
        }
    }
}

$oShop_Items = Core_Entity::factory('Shop_Item');
$aShop_Items = $oShop_Items->findAll();

foreach ($aShop_Items as $oShop_Item) {
    convert($oShop_Item);
}

$oInformationsystem_Items = Core_Entity::factory('Informationsystem_Item');
$aInformationsystem_Items = $oInformationsystem_Items->findAll();

foreach ($aInformationsystem_Items as $oInformationsystem_Item) {
    convert($oInformationsystem_Item);
}
