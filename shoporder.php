<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_ShopOrder
{
    // Получение полного имени товара с учетом назания из заказа
    public static function getFullName($oShop_Order_Item)
    {
        $sep = ' :: ';
        $oShop_Item = $oShop_Order_Item->Shop_Item;
        return $oShop_Item->modification_id
            ? $oShop_Item->Modification->name . $sep . $oShop_Order_Item->name
            : $oShop_Order_Item->name;
    }
}