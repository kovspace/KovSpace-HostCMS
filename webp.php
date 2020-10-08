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

$oObjects = Core_Entity::factory('Shop_Item');
$aObjects = $oObjects->findAll();

foreach ($aObjects as $oObject) {
    $dir = $oObject->getItemPath();
    convert($oObject, $dir, 'image_large');
    convert($oObject, $dir, 'image_small');
}


/* Shop Groups */

$oObjects = Core_Entity::factory('Shop_Group');
$aObjects = $oObjects->findAll();

foreach ($aObjects as $oObject) {
    $dir = $oObject->getGroupPath();
    convert($oObject, $dir, 'image_large');
    convert($oObject, $dir, 'image_small');
}


/* Informationsystem Items */

$oObjects = Core_Entity::factory('Informationsystem_Item');
$aObjects = $oObjects->findAll();

foreach ($aObjects as $oObject) {
    $dir = $oObject->getItemPath();
    convert($oObject, $dir, 'image_large');
    convert($oObject, $dir, 'image_small');
}


/* Informationsystem Groups */

$oObjects = Core_Entity::factory('Informationsystem_Group');
$aObjects = $oObjects->findAll();

foreach ($aObjects as $oObject) {
    $dir = $oObject->getGroupPath();
    convert($oObject, $dir, 'image_large');
    convert($oObject, $dir, 'image_small');
}
