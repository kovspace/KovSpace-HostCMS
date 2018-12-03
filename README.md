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
