<?php

/* PNG to WEBP */
/* Shops and Information Systems */

require_once(dirname(__FILE__, 3) . '/bootstrap.php');

function convert(object $object, string $dir, string $property): void
{
    if (!$dir) {
        return;
    }

    if (!$image = $object->$property) {
        return;
    }

    if (str_ends_with(strtolower($image), '.webp')) {
        return;
    }

    $path = $dir . $image;
    if (!file_exists($path)) {
        return;
    }

    $ext = strtolower(Core_File::getExtension($image));
    if (!$ext) {
        $dotpos = strrpos($image, '.');
        if ($dotpos === false) {
            return;
        }
        $ext = strtolower(substr($image, $dotpos + 1));
    }

    $name = $ext
        ? substr($image, 0, -(strlen($ext) + 1))
        : $image;
    $newImage = $name . '.webp';
    $newPath = $dir . $newImage;

    $mimes = [
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    $mime = mime_content_type($path);
    $mimeExt = $mimes[$mime] ?? null;

    if (!$mimeExt) {
        return;
    }

    if ($mimeExt != $ext) {
        $imageMime = $name . '.' . $mimeExt;
        $pathMime = $dir . $imageMime;
        rename($path, $pathMime);
        $object->$property = $imageMime;
        $object->save();
        echo 'Changed extension from ' . $ext . ' to ' . $mimeExt . PHP_EOL;
        $ext = $mimeExt;
        $path = $pathMime;
        $image = $imageMime;
    }

    echo $path . PHP_EOL;

    $im = KovSpace_Imgorientation::loadImage($path, $ext);

    if ($im) {
        imagewebp($im, $newPath);
        imagedestroy($im);

        if (file_exists($newPath)) {
            $object->$property = $newImage;
            $object->save();

            if ($property === 'image_large' && method_exists($object, 'setLargeImageSizes')) {
                $object->setLargeImageSizes();
            } elseif ($property === 'image_small' && method_exists($object, 'setSmallImageSizes')) {
                $object->setSmallImageSizes();
            }

            unlink($path);
        }
    }
}

function convertProperties(int $entity_id, string $dir): void
{
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

function start(string $model, string $method): void
{
    $oObjects = Core_Entity::factory($model);
    $aObjects = $oObjects->findAll();
    foreach ($aObjects as $oObject) {
        $dir = $oObject->$method();
        convert($oObject, $dir, 'image_large');
        convert($oObject, $dir, 'image_small');
        convertProperties($oObject->id, $dir);
    }
}

if (Core::moduleIsActive('shop')) {
    start('Shop_Item', 'getItemPath');
    start('Shop_Group', 'getGroupPath');
}

if (Core::moduleIsActive('informationsystem')) {
    start('Informationsystem_Item', 'getItemPath');
    start('Informationsystem_Group', 'getGroupPath');
}
