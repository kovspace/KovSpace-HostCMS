<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_ShopGroup
{
    // Получить корневые группы магазины
    public static function root($oShop): array
    {
        $oShop_Groups = $oShop->Shop_Groups;
        $oShop_Groups->queryBuilder()
            ->where('active', '=', 1)
            ->where('parent_id', '=', 0)
            ->orderBy('sorting', 'asc');
        return $oShop_Groups->findAll();
    }
}
