<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_ShopItem
{
    // Получаем коллекцию доп. свойств
    public static function propertyCollection($oShop_Item)
    {
        $linkedObject = Core_Entity::factory('Shop_Item_Property_List', $oShop_Item->Shop->id);
        $aProperties = $linkedObject->Properties->findAll();

        if ($aProperties) {
            foreach ($aProperties as $oProperty) {
                foreach ($oProperty->getValues($oShop_Item->id) as $oValue) {
                    if ($oProperty->type == 2) {
                        $aValues[] = [
                            'tag_name' => $oProperty->tag_name,
                            'dir_id' => $oProperty->property_dir_id,
                            'dir_name' => $oProperty->Property_Dir->name,
                            'type' => $oProperty->type,
                            'sorting' => $oProperty->sorting,
                            'file' => $oValue->file ? $oShop_Item->getItemHref() . $oValue->file : null,
                            'file_small' => $oValue->file_small ? $oShop_Item->getItemHref() . $oValue->file_small : null,
                        ];
                    } else {
                        $aValues[] = [
                            'tag_name' => $oProperty->tag_name,
                            'dir_id' => $oProperty->property_dir_id,
                            'dir_name' => $oProperty->Property_Dir->name,
                            'type' => $oProperty->type,
                            'sorting' => $oProperty->sorting,
                            'value'   => $oValue->value,
                        ];
                    }
                }
            }
        }

        return $aValues ?? [];
    }

    // Получем значение свойства из коллекции по тегу
    public static function propertyCollectionByTag($aProperties, $tagname)
    {
        foreach ($aProperties as $aProperty) {

        }
    }

    // Получить массив свойств по тегу
    public static function propertiesByTag($oShop_Item, $tagname): array
    {
		$linkedObject = Core_Entity::factory('Shop_Item_Property_List', $oShop_Item->Shop->id);
		$oProperties = $linkedObject->Properties;
		$oProperties->queryBuilder()
			->where('tag_name', '=', $tagname);
		$aProperties = $oProperties->findAll();

		if ($aProperties) {
            foreach ($aProperties as $oProperty) {
                foreach ($oProperty->getValues($oShop_Item->id) as $oValue) {
                    if (get_class($oValue) == 'Property_Value_File_Model') {
                        $aValues[] = [
                            'file' => $oShop_Item->getItemHref() . $oValue->file,
                            'file_small' => $oShop_Item->getItemHref() . $oValue->file_small,
                        ];
                    } else {
                        $aValues[] = $oValue->value;
                    }
                }
            }
		}

        return $aValues ?? [];
    }

    // Получить значение свойства по тегу
    public static function propertyByTag($oShop_Item, $tagname)
    {
        return self::propertiesByTag($oShop_Item, $tagname)[0] ?? null;
    }
}