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
<?php new KovSpace_Form(1); // InformationSystem ID ?>
```

```html
<form method="post">
    <input type="hidden" name="form" value="1">
    <input type="hidden" name="url" value="">
    <div class="form-group">
        <input class="form-control" type="text" name="name" placeholder="Name:" value="<?= Core_Array::getPost('name') ?>">
    </div>
    <div class="form-group">
        <input class="form-control" type="text" name="phone" placeholder="Phone:" value="<?= Core_Array::getPost('phone') ?>">
    </div>
    <div class="form-group">
        <input class="form-control" type="text" name="email" placeholder="Email:" value="<?= Core_Array::getPost('email') ?>">
    </div>
    <div class="form-group">
        <textarea class="form-control" rows="3" name="comment" placeholder="Comment:"><?= Core_Array::getPost('comment') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
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
