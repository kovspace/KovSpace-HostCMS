<?php

/* PNG to WEBP */
/* Shops and Information Systems */

require_once(dirname(__FILE__) . '/../../' . 'bootstrap.php');

function convert($object, $dir, $property) {
    if (!$dir) return;
    $image = $object->$property;
    if (!$image) return;

    $path = $dir . $image;
    if (!file_exists($path)) return;

    $dotpos = strrpos($image, '.');
    $name = substr($image, 0, $dotpos);
    $ext = substr($image, $dotpos);
    $new_image = $name . '.webp';
    $new_path = $dir . $new_image;

    if ($ext == '.png') {
        echo $path . PHP_EOL;
        $im = @imagecreatefrompng($path);
        if (!$im) {
            echo 'Error: incorrect format';
            // Возможно это JPEG
            $im = @imagecreatefromjpeg($path);
        } else {
            // PNG
            imagepalettetotruecolor($im);
        }

        imagewebp($im, $new_path);
        imagedestroy($im);

        if (file_exists($new_path)) {
            $object->$property = $new_image;
            $object->save();
            unlink($path);
        }
    }
}

function convertProperties($entity_id, $dir) {
    $oProperty_Value_Files = Core_Entity::factory('Property_Value_File');
    $oProperty_Value_Files->queryBuilder()
        ->where('entity_id', '=', $entity_id)
        ->where('file', '!=', '')
        ->setOr()
        ->where('entity_id', '=', $entity_id)
        ->where('file_small', '!=', '');
    $aProperty_Value_Files = $oProperty_Value_Files->findAll();
    foreach ($aProperty_Value_Files as $oProperty_Value_File) {
        convert($oProperty_Value_File, $dir, 'file');
        convert($oProperty_Value_File, $dir, 'file_small');
    }
}

function start($model, $method) {
    $oObjects = Core_Entity::factory($model);
    $aObjects = $oObjects->findAll();
    foreach ($aObjects as $oObject) {
        $dir = $oObject->$method();
        convert($oObject, $dir, 'image_large');
        convert($oObject, $dir, 'image_small');
        convertProperties($oObject->id, $dir);
    }
}

start('Shop_Item', 'getItemPath');
start('Shop_Group', 'getGroupPath');
start('Informationsystem_Item', 'getItemPath');
start('Informationsystem_Group', 'getGroupPath');
