<?php

/* PNG to WEBP */
/* Shop and Information System main images */

require_once(dirname(__FILE__) . '/../../' . 'bootstrap.php');

function convert($object, $dir, $property) {

    if (!$dir) return;
    $image = $object->$property;
    if (!$image) return;

    $path = $dir . $image;
    if (!$path || !file_exists($path)) return;

    $dotpos = strrpos($image, '.');
    $name = substr($image, 0, $dotpos);
    $ext = substr($image, $dotpos);
    $new_image = $name . '.webp';
    $new_path = $dir . $new_image;

    if ($ext == '.png') {
        echo $path . PHP_EOL;
        $im = @imagecreatefrompng($path);
        if (!$im) echo 'Error: ' . $path . PHP_EOL;

        imagepalettetotruecolor($im); // for png
        imagewebp($im, $new_path);
        imagedestroy($im);

        if (file_exists($new_path)) {
            $object->$property = $new_image;
            $object->save();
            unlink($path);
        }
    }
}

/* Shop Items */

$oShop_Items = Core_Entity::factory('Shop_Item');
$aShop_Items = $oShop_Items->findAll();

foreach ($aShop_Items as $oShop_Item) {
    $dir = $oShop_Item->getItemPath();
    convert($oShop_Item, $dir, 'image_large');
    convert($oShop_Item, $dir, 'image_small');
}

/* Shop Groups */

$oShop_Groups = Core_Entity::factory('Shop_Group');
$aShop_Groups = $oShop_Groups->findAll();

foreach ($aShop_Groups as $oShop_Group) {
    $dir = $oShop_Group->getGroupPath();
    convert($oShop_Group, $dir, 'image_large');
    convert($oShop_Group, $dir, 'image_small');
}

/* Informationsystem Items */

$oInformationsystem_Items = Core_Entity::factory('Informationsystem_Item');
$aInformationsystem_Items = $oInformationsystem_Items->findAll();

foreach ($aInformationsystem_Items as $oInformationsystem_Item) {
    $dir = $oShop_Item->getItemPath();
    convert($oShop_Item, $dir, 'image_large');
    convert($oShop_Item, $dir, 'image_small');
}
