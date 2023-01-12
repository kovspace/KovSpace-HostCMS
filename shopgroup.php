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

    // Получить массив id для подгрупп
    public static function getSubGroupIds(Shop_Group_Model $oShop_Group, $withParent = false): array
    {
        $ids = [];
        if ($withParent) {
            $ids[] = $oShop_Group->id;
        }
        $aSub_Groups = $oShop_Group->Shop_Groups->getAllByActive(1);
        foreach ($aSub_Groups as $oSub_Group) {
            $ids[] = $oSub_Group->id;
        }
        return $ids;
    }
}
