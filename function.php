<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Function
{
    // Получение значения доп. свойства элемента
    public static function getItemPropertyValue($oItem, $propertyId) {
        $aProperties = $oItem->getPropertyValues(false, array($propertyId));
        if ($aProperties) {
            $oProperty = $aProperties[0];
            return $oProperty->value;
        }
    }
}
