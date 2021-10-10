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
                        $file = $oValue->file ? $oShop_Item->getItemHref() . $oValue->file : null;
                        $file_small = $oValue->file_small ? $oShop_Item->getItemHref() . $oValue->file_small : null;
                        if (!$file && !$file_small) {
                            continue;
                        }
                        $aValue = [
                            'file' => $file,
                            'file_small' => $file_small,
                        ];
                    } else {
                        if (!$value = $oValue->value) {
                            continue;
                        }
                        $aValue = [
                            'value'   => $value,
                        ];
                    }
                    $aValues[] = [
                        'tag_name' => $oProperty->tag_name,
                        'name'  => $oProperty->name,
                        'dir_id' => $oProperty->property_dir_id,
                        'dir_name' => $oProperty->Property_Dir->name,
                        'type' => $oProperty->type,
                        'sorting' => $oProperty->sorting
                    ] + $aValue;

                }
            }
        }

        return $aValues ?? [];
    }

    // Фильтруем коллекцию по полю и значению
    public static function filterCollection($aProperties, $field, $value): array
    {
        foreach ($aProperties as $oProperty) {
            if (isset($oProperty[$field]) && $oProperty[$field] == $value) {
                $array[] = $oProperty;
            }
        }
        return $array ?? [];
    }


    // Получем массив свойств из коллекции по тегу
    public static function collectionValuesByTag($aProperties, $tagname)
    {
        foreach ($aProperties as $aProperty) {
            if ($aProperty['tag_name'] == $tagname) {
                if ($aProperty['type'] == 2) {
                    $aValues[] = $aProperty['file'];
                } else {
                    $aValues[] = $aProperty['value'];
                }
            }
        }
        return $aValues ?? [];
    }

    // Получаем значение свойства из коллекции по тегу
    public static function collectionValueByTag($aProperties, $tagname)
    {
        return self::collectionValuesByTag($aProperties, $tagname)[0] ?? null;
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