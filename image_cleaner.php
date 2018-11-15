<?php
require_once('bootstrap.php');

if ($dirname = Core_Array::getGet('dirname')) {
    if (Core_Array::getGet('delete')) {
        cleanImages($dirname, 1);
    } else {
        cleanImages($dirname, 0);
    }
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

    if (!$isFound) return TRUE;
}

function cleanImages($dirname, $isDelete = 0) {

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

                    if (is_file($pathName))
                    {
                        $result = checkDatabase($pathName);
                        if ($result) {
                            if ($isDelete) {
                                unlink($pathName);
                            } else {
                                echo date ("d F Y H:i", filemtime($pathName));
                                echo '<br>';
                                echo $pathName;
                                echo '<br><br>';
                            }
                        }
                    }
                    elseif (is_dir($pathName))
                    {
                        if(isDirEmpty($pathName)) {
                            rmdir($pathName);
                        } else {
                            cleanImages($pathName, $isDelete);
                        }
                    }
                }
            }

            closedir($dh);
            clearstatcache();
        }
    }
}

$dir = CMS_FOLDER.'upload/';
$aFiles = scandir($dir);
$aPaths = array();

foreach ($aFiles as $file) {
    $pathName = $dir.$file;
    if ($file == strstr($file, 'shop_') || $file == strstr($file, 'information_system_')) {
        if (is_dir($pathName)) {
            $aPaths[] = $pathName;
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
    <div class="my-2 container">
        <h1>KovSpace Image Cleaner</h1>
        <div class="my-4" id="result"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js"></script>

    <script>
        var aErrors = [];
        var aPaths = [
            <?php foreach ($aPaths as $path): ?>
                '<?=$path?>',
            <?php endforeach ?>
        ];

        if (aPaths.length) {
            check(0);
        } else {
            $('#result').html('No dirs found');
        }

        function check(i) {
            $('#result').prepend('<div class="my-2" id="path-'+i+'"><div class="path font-weight-bold">'+aPaths[i]+'</div><div class="status"><span class="blink">Checking...</span></div></div>');
            $.ajax({
                url: '<?=Core::$url["path"]?>?dirname='+aPaths[i],
                cache: false,
                success: function(html)
                {
                    if (html == '') {
                        $('#path-'+i+' .status').html('<span class="text-success">OK</span>');
                    } else {
                        aErrors.push(aPaths[i]);
                        $('#path-'+i).html('<span class="text-danger">'+html+'</span>');
                    }

                    if (i+1 < aPaths.length) {
                        check(i+1);
                    } else {
                        if (aErrors.length) {
                            $('#result').prepend('<button class="js-fix mb-2 btn btn-primary">Fix all erros</button>');
                        } else {
                            $('#result').prepend('<div class="d-inline-block alert alert-info">No errors found</div>');
                        }
                    }
                }
            });
        }

        function clean(i) {
            $.ajax({
                url: '<?=Core::$url["path"]?>?dirname='+aErrors[i]+'&delete=1',
                cache: false,
                success: function(html)
                {
                    if (i+1 < aErrors.length) {
                        clean(i+1);
                    } else {
                        $('#result').html('<div class="d-inline-block alert alert-info">All bugs fixed</div>');
                    }
                }
            });
        }

        $(document).ajaxComplete(function() {
            $('.js-fix').click(
                function () {
                    clean(0);
                }
            );
        });
    </script>

</body>
</html>