<?php
require_once('bootstrap.php');
if (!Core_Auth::logged()) exit('Access Denied!');

$dir = CMS_FOLDER.'upload/';
$filemapJson = CMS_FOLDER.'hostcmsfiles/cache/kic_filemap.json';
$aFilemap = [];

// AJAX-запрос
if (Core_Array::getGet('dirname')) {
    $dirname = $dir.Core_Array::getGet('dirname');
    if (Core_Array::getGet('offset')) {
        $offset = Core_Array::getGet('offset');
        $aFilemap = json_decode(file_get_contents($filemapJson));
    } else {
        $offset = 0;
        filemap($dirname, $aFilemap);
        file_put_contents($filemapJson,json_encode($aFilemap));
    }
    $response = checkFiles($aFilemap, $offset);
    echo json_encode($response);
    exit();
}

function isDirEmpty($dirname) {
    if (!is_readable($dirname)) return NULL;
    return (count(scandir($dirname)) == 2);
}

function checkDatabase($pathName) {

    // Autodetect module by path
    if (strstr($pathName, 'information_system_')) {
        $module = 'information_system';
    } elseif (strstr($pathName, 'shop_')) {
        $module = 'shop';
    }

    $fileName = basename($pathName);
    $isFound = FALSE;

    if (strstr($pathName, 'watermark')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shops'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('watermark_file')
                ->from('shops')
                ->where('watermark_file', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
        if (!$isFound && $module == 'information_system') {
            // Check in table 'informationsystems'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('watermark_file')
                ->from('informationsystems')
                ->where('watermark_file', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (strstr($pathName, 'producers')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shop_producers'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large', 'image_small')
                ->from('shop_producers')
                ->open()
                ->where('image_large', '=', $fileName)
                ->setOr()
                ->where('image_small', '=', $fileName)
                ->close()
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (strstr($pathName, 'eitems')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shop_item_digitals'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('filename')
                ->from('shop_item_digitals')
                ->where('filename', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (strstr($pathName, 'sellers')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shop_sellers'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large', 'image_small')
                ->from('shop_sellers')
                ->open()
                ->where('image_large', '=', $fileName)
                ->setOr()
                ->where('image_small', '=', $fileName)
                ->close()
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (strstr($pathName, 'small_item_image')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shop_items'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_small')
                ->from('shop_items')
                ->where('image_small', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    } elseif (strstr($pathName, 'item_image')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shop_items'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large')
                ->from('shop_items')
                ->where('image_large', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (strstr($pathName, 'small_group_') || strstr($pathName, 'small_shop_group')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shop_groups'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_small')
                ->from('shop_groups')
                ->where('image_small', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    } elseif (strstr($pathName, 'group_')) {
        if (!$isFound && $module == 'shop') {
            // Check in table 'shop_groups'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large')
                ->from('shop_groups')
                ->where('image_large', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (strstr($pathName, 'small_item_') || strstr($pathName, 'small_information_items_')) {
        if (!$isFound && $module == 'information_system') {
            // Check in table 'informationsystem_items'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_small')
                ->from('informationsystem_items')
                ->where('image_small', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    } elseif (strstr($pathName, 'item_') || strstr($pathName, 'information_items_')) {
        if (!$isFound && $module == 'information_system') {
            // Check in table 'informationsystem_items'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large')
                ->from('informationsystem_items')
                ->where('image_large', '=', $fileName)
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (strstr($pathName, 'property_')) {
        if (!$isFound) {
            // Check in table 'property_value_files'
            $oCore_QueryBuilder_Select = Core_QueryBuilder::select('file', 'file_small')
                ->from('property_value_files')
                ->open()
                ->where('file', '=', $fileName)
                ->setOr()
                ->where('file_small', '=', $fileName)
                ->close()
                ->limit(1);
            $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
            if ($row) $isFound = TRUE;
        }
    }

    if (!$isFound && $module == 'shop') {
        // Check in table 'shop_items'
        $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large', 'image_small')
            ->from('shop_items')
            ->open()
            ->where('image_large', '=', $fileName)
            ->setOr()
            ->where('image_small', '=', $fileName)
            ->close()
            ->limit(1);
        $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
        if ($row) $isFound = TRUE;
    }

    if (!$isFound && $module == 'shop') {
        // Check in table 'shop_groups'
        $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large', 'image_small')
            ->from('shop_groups')
            ->open()
            ->where('image_large', '=', $fileName)
            ->setOr()
            ->where('image_small', '=', $fileName)
            ->close()
            ->limit(1);
        $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
        if ($row) $isFound = TRUE;
    }

    if (!$isFound && $module == 'shop') {
        // Check in table 'property_value_files'
        $oCore_QueryBuilder_Select = Core_QueryBuilder::select('file', 'file_small')
            ->from('property_value_files')
            ->open()
            ->where('file', '=', $fileName)
            ->setOr()
            ->where('file_small', '=', $fileName)
            ->close()
            ->limit(1);
        $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
        if ($row) $isFound = TRUE;
    }

    if (!$isFound && $module == 'information_system') {
        // Check in table 'informationsystem_items'
        $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large', 'image_small')
            ->from('informationsystem_items')
            ->open()
            ->where('image_large', '=', $fileName)
            ->setOr()
            ->where('image_small', '=', $fileName)
            ->close()
            ->limit(1);
        $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
        if ($row) $isFound = TRUE;
    }

    if (!$isFound && $module == 'information_system') {
        // Check in table 'informationsystem_groups'
        $oCore_QueryBuilder_Select = Core_QueryBuilder::select('image_large', 'image_small')
            ->from('informationsystem_groups')
            ->open()
            ->where('image_large', '=', $fileName)
            ->setOr()
            ->where('image_small', '=', $fileName)
            ->close()
            ->limit(1);
        $row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();
        if ($row) $isFound = TRUE;
    }

    if (!$isFound) return TRUE;
}

// Создаем карту файлов
function filemap($dirname, &$aFilemap) {
    if (is_dir($dirname) && !is_link($dirname))
    {
        if ($dh = @opendir($dirname))
        {
            while (($file = readdir($dh)) !== FALSE)
            {
                if ($file != '.' && $file != '..')
                {
                    clearstatcache();
                    $pathName = $dirname . DIRECTORY_SEPARATOR . $file;
                    if (is_file($pathName)) {
                        $aFilemap[] = $pathName;
                    }
                    elseif (is_dir($pathName))
                    {
                        if(isDirEmpty($pathName)) {
                            rmdir($pathName);
                        } else {
                            filemap($pathName, $aFilemap);
                        }
                    }
                }
            }
            closedir($dh);
            clearstatcache();
        }
    }
}

// Проверяем файлы
function checkFiles($aFiles, $offset = 0) {

    static $start;
    static $response = [];

    // Стартовая позиция
    $start = $start === NULL
        ? $offset
        : 0;

    // Цикл закончился
    if ($offset >= count($aFiles)) {
        $response['result'] = 'OK';
        return $response;
    }

    // Если кратно 100
    if ($offset > $start && $offset % 100 == 0) {
        $response['offset'] = $offset;
        return $response;
    }

    if (!empty($aFiles[$offset])) {
        $pathName = $aFiles[$offset];

        $result = checkDatabase($pathName);
        if ($result) {
            $response['deleted'][] = $pathName;
            unlink($pathName);
        }
        return checkFiles($aFiles, $offset+1);
    }
}

$aFiles = scandir($dir);
$aPaths = array();

foreach ($aFiles as $file) {
    $pathName = $dir.$file;
    if ($file == strstr($file, 'shop_') || $file == strstr($file, 'information_system_')) {
        if (is_dir($pathName)) {
            $aPaths[] = $file;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KovSpace Image Cleaner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css">
    <style>
        img {
            max-width: 100%;
            max-height: 40px;
        }
        body {
            background: #f6f6f6;
        }
        .blink{
            animation: blink 1s infinite;
        }
        @keyframes blink{
            0%{opacity: 1;}
            75%{opacity: 1;}
            76%{ opacity: 0;}
            100%{opacity: 0;}
	    }
    </style>
</head>
<body>
    <div class="py-2 bg-dark text-white">
        <div class="container">
            <h1>KovSpace Image Cleaner</h1>
        </div>
    </div>

    <div class="my-2 py-4 container content bg-white">
        <button id="startBtn" class="d-none btn btn-primary">Запустить проверку</button>
        <button id="stopBtn" class="d-none btn btn-danger">Остановить проверку</button>
        <div class="mt-4" id="result"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js"></script>

    <script>
        let aErrors = []
        let aPaths = [
            <?php foreach ($aPaths as $path): ?>
                '<?=$path?>',
            <?php endforeach ?>
        ]

        let checking = 0

        // Запустить проверку
        $('#startBtn').click(function() {
                $(this).addClass('d-none')
                $('#result').empty()
                $('#stopBtn').removeClass('d-none')
                checking = 1
                check(0)
            }
        )

        // Остановить проверку
        $('#stopBtn').click(function() {
                $(this).addClass('d-none')
                $('#startBtn').removeClass('d-none')
                checking = 0
                check(0)
            }
        )

        if (aPaths.length) {
            $('#startBtn').removeClass('d-none')
        } else {
            $('#result').html('No dirs found')
        }

        function ajaxRequest(url) {
            $.ajax({
                url: url,
                cache: false,
                success: function(json) {

                }
            })
        }

        function check(i, offset = 0) {
            if (checking == 0) return
            if (!offset) {
                $('#result').prepend('<div class="my-2" id="path-'+i+'"><div class="path font-weight-bold">'+aPaths[i]+'</div><div class="status"><span class="blink text-info">Checking...</span></div></div>')
            } else {
                $('#path-'+i+' .status').html('<span class="blink text-info">Checking... '+offset+'</span>')
            }
            let url = '<?=Core::$url["path"]?>?dirname='+aPaths[i]+'&offset='+offset
            $.ajax({
                url: url,
                dataType: 'json',
                cache: false,
                success: function(json) {

                    if (!checking) {
                        $('#path-'+i+' .status').html('<span class="text-danger">Stopped</span>')
                        return
                    }

                    if (json.deleted) {
                        let cmsFolder = '<?=CMS_FOLDER?>';
                        $.each(json.deleted, function(pos, item) {
                            item = item.replace(cmsFolder, '')
                            $('#result #path-'+i).append('<div class="text-danger">deleted: /'+item+'</div>')
                        });
                    }
                    if (json.result == 'OK') {
                        $('#path-'+i+' .status').html('<span class="text-success">OK</span>')
                        if (i+1 < aPaths.length) {
                            check(i+1)
                        } else {
                            $('#stopBtn').addClass('d-none')
                            $('.content').prepend('<div class="d-inline-block alert alert-info">Done!</div>')
                        }
                    }
                    if (json.offset) {
                        check(i, json.offset)
                    }
                }
            })
        }
    </script>
</body>
</html>