<?php

/* Shop's PNG to WEBP */

require_once(dirname(__FILE__) . '/../../' . 'bootstrap.php');

function convert($oItem) {
    $image = $oItem->image_large;
    $size = 'large';
    webp($oItem, $image, $size);

    $image = $oItem->image_small;
    $size = 'small';
    webp($oItem, $image, $size);
}

function webp($oItem, $image, $size) {
    if ($image) {
        $path = $oItem->getItemPath() . $image;
        {
            if (file_exists($path)) {
                $dotpos = strrpos($image, '.');
                $name = substr($image, 0, $dotpos);
                $ext = substr($image, $dotpos);
                $newname = $name . '.webp';
                $newpath = $oItem->getItemPath() . $newname;

                if ($ext == '.png') {
                    $im = imagecreatefrompng($path);

                    // PNG convert
                    imagepalettetotruecolor($im);
                    imagealphablending($im, true);
                    imagesavealpha($im, true);

                    imagewebp($im, $newpath);
                    imagedestroy($im);

                    if ($size == 'large') {
                        $oItem->image_large = $newname;
                    }

                    if ($size == 'small') {
                        $oItem->image_small = $newname;
                    }

                    $oItem->save();
                    unlink($path);
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