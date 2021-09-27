## Install Path

```
/modules/kovspace/
```

## HostCMS Template Helper

```php
$Template = new KovSpace_Template();

// Methods
$Template
    ->detectReferer()
    ->imageTimestamp()
    ->imageCDN()
    ->informationsystemCDN()
    ->shopCDN()
    ->structureCDN()
    ->showDoctype()
    ->showHeadOpen()
    ->showHeadClose()
    ->showMeta()
    ->showViewport( $width = NULL )
    ->showFavicon()
    ->showVendorCSS( $url )
    ->showSectionCSS()
    ->showCSS( $file )
    ->showJS( $file )
    ->showKovSpace()
    ->showHostCMS()
    ->showPrivacy()
    ->googleTagManager( $id )
    ->googleTagManagerNoScript( $id )
    ->gTag( $id );

// Properties
$Template
    ->title
    ->description
    ->keywords
    ->root
    ->path
    ->section
    ->object
    ->objectGroupId
    ->objectItemId
    ->kovspace
    ->hostcms
    ->emailFrom;
```


## HostCMS Form Handler

```php
<?php
$formId = 9; // Information System ID
$oForm = new KovSpace_Form($formId);
?>

<br><h2 id="form<?=$formId?>">Форма обратной связи</h2>

<?php if ($oForm->error): ?>
    <div class="alert alert-danger"><?=$oForm->error?></div>
<?php elseif ($oForm->success): ?>
    <div class="alert alert-success"><?=$oForm->success?></div>
<?php endif ?>

<!-- Prevent Page Refresh -->
<script>
if (window.history.replaceState) {
    window.history.replaceState( null, null, window.location.href );
}
</script>

<form method="post" action="#form<?=$formId?>">
    <input type="hidden" name="form" value="<?=$formId?>">
    <input type="hidden" name="url" value="">
    <div class="form-group">
        <input class="form-control" type="text" name="name" placeholder="Ваше имя" value="<?= Core_Array::getPost('name') ?>">
    </div>
    <div class="form-group">
        <input class="form-control" type="text" name="phone" placeholder="Ваш телефон" value="<?= Core_Array::getPost('phone') ?>">
    </div>
    <div class="form-group">
        <input class="form-control" type="text" name="email" placeholder="Ваш email" value="<?= Core_Array::getPost('email') ?>">
    </div>
    <div class="form-group">
        <textarea class="form-control" rows="3" name="comment" placeholder="Ваш комментарий"><?= Core_Array::getPost('comment') ?></textarea>
    </div>
    <?php if (!$oForm->success): ?>
        <button type="submit" class="btn btn-primary">Отправить</button>
    <?php endif ?>
</form>
```

## HostCMS Form2 for paid Edition

```php
<?php $form = new KovSpace_Form2(21); ?>
<div>some content here</div>
<?php $form->show() ?>
```

## HostCMS Pagination

```php
<?php
$page = 1;
$offset = 0;
$limit = Core_Array::getGet('limit') ?? 10;

if (Core_Array::getGet('page')) {
    $page = Core_Array::getGet('page');
    if ($page > 1) {
        $offset = ($page - 1) * $limit;
    } else {
        KovSpace_Function::urlParamRedirect('page', NULL);
    }
}

$oShop_Orders = Core_Entity::factory('Shop_Order');
$oShop_Orders->queryBuilder()
    ->sqlCalcFoundRows()
    ->orderBy('id', 'DESC')
    ->offset($offset)
    ->limit($limit);

// Count Pages
$row = Core_QueryBuilder::select(array('FOUND_ROWS()', 'count'))->execute()->asAssoc()->current();
$count = $row['count'];
$pages = ceil($count / $limit);
?>
```

```php
<?php KovSpace_Pagination::show($pages, $page); ?>
```
