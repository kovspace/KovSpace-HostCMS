<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_ShopItem
{
    /**
     * Получение полного имени товара с учетом модификации
     */
    public static function getFullName(Shop_Item_Model $oShop_Item): string
    {
        $sep = ' :: ';
        return $oShop_Item->modification_id
            ? $oShop_Item->Modification->name . $sep . $oShop_Item->name
            : $oShop_Item->name;
    }

    /**
     * Получение относительной ссылки на товар
     */
    public static function getRelativeUrl(Shop_Item_Model $oShop_Item): string
    {
        return $oShop_Item->Shop->Structure->getPath() . $oShop_Item->getPath();
    }


    /**
     * Получение абсолютной ссылки на товар
     */
    public static function getAbsoluteUrl(Shop_Item_Model $oShop_Item): string
    {
        $href = self::getRelativeUrl($oShop_Item);
        $oSite = $oShop_Item->Shop->Site;
        return 'https://' . $oSite->name . $href;
    }

    /**
     * Получение превью товара
     */
    public static function getImageSmall(Shop_Item_Model $oShop_Item): string
    {
        if ($oShop_Item->modification_id) {
            $oShop_Item = $oShop_Item->Modification;
        }
        return $oShop_Item->image_small
            ? $oShop_Item->getItemHref() . $oShop_Item->image_small
            : '/upload/blank/100x100.webp';
    }

    /**
     * Получение всех модификаций
     * @param Shop_Item_Model $oShop_Item
     * @return Shop_Item_Model[]
     */
    public static function getAllModifications(Shop_Item_Model $oShop_Item): array
    {
        $oShop_Item_Modifications = $oShop_Item->Modifications;
        $oShop_Item_Modifications->queryBuilder()
            ->orderBy('name', 'asc');
        return $oShop_Item_Modifications->findAll();
    }

    /**
     * Фильтруем модификации без остатков
     * @return Shop_Item_Model[]
     */
    public static function filterRestModifications(array $aShop_Item_Modifications): array
    {
        $aRestModifications = [];
        foreach ($aShop_Item_Modifications as $oShop_Item_Modification) {
            if ($oShop_Item_Modification->getRest() > 0) {
                $aRestModifications[] = $oShop_Item_Modification;
            }
        }
        return $aRestModifications;
    }

    /**
     * Получение модификаций с остатками (можно вывести есть вообще модификации)
     */
    public static function getModifications(Shop_Item_Model $oShop_Item, bool $returnHasModifications = false): array
    {
        $aShop_Item_Modifications = self::getAllModifications($oShop_Item);
        $hasModifications = (bool)count($aShop_Item_Modifications);
        $aRestModifications = self::filterRestModifications($aShop_Item_Modifications);
        return $returnHasModifications
            ? [$aRestModifications, $hasModifications]
            : $aRestModifications;
    }

    /**
     * Получаем коллекцию доп. свойств
     */
    public static function propertyCollection(Shop_Item_Model $oShop_Item): array
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
                            'value' => $value,
                        ];
                    }
                    $aValues[] = [
                            'tag_name' => $oProperty->tag_name,
                            'name' => $oProperty->name,
                            'dir_id' => $oProperty->property_dir_id,
                            'dir_name' => $oProperty->Property_Dir->name,
                            'type' => $oProperty->type,
                            'sorting' => $oProperty->sorting,
                            'valueSorting' => $oValue->sorting,
                        ] + $aValue;
                }
            }
        }

        return $aValues ?? [];
    }

    /**
     * Фильтруем коллекцию по полю и значению
     */
    public static function filterCollection(array $aProperties, string $field, string $value): array
    {
        foreach ($aProperties as $oProperty) {
            if (isset($oProperty[$field]) && $oProperty[$field] == $value) {
                $array[] = $oProperty;
            }
        }
        return $array ?? [];
    }

    /**
     * Получем массив свойств из коллекции по тегу
     */
    public static function collectionValuesByTag(array $aProperties, string $tagname): array
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

    /**
     * Получаем значение свойства из коллекции по тегу
     */
    public static function collectionValueByTag(array $aProperties, string $tagname)
    {
        return self::collectionValuesByTag($aProperties, $tagname)[0] ?? null;
    }

    /**
     * Получить массив свойств по тегу
     */
    public static function getPropertiesByTag(Shop_Item_Model $oShop_Item, string $tagname): array
    {
        $linkedObject = Core_Entity::factory('Shop_Item_Property_List', $oShop_Item->Shop->id);
        $oProperties = $linkedObject->Properties;
        $oProperties->queryBuilder()
            ->where('tag_name', '=', $tagname);
        return $oProperties->findAll();
    }

    /**
     * Получить массив значений свойств по тегу
     */
    public static function propertiesByTag(Shop_Item_Model $oShop_Item, string $tagname): array
    {
        $aProperties = self::getPropertiesByTag($oShop_Item, $tagname);

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

    /**
     * Получить объект свойства по тегу
     */
    public static function getPropertyByTag(Shop_Item_Model $oShop_Item, string $tagname)
    {
        return self::getPropertiesByTag($oShop_Item, $tagname)[0] ?? null;
    }

    /**
     * Получить значение свойства по тегу
     */
    public static function propertyByTag(Shop_Item_Model $oShop_Item, string $tagname)
    {
        return self::propertiesByTag($oShop_Item, $tagname)[0] ?? null;
    }

    /**
     * Записать значение свойства
     * @throws Core_Exception
     */
    public static function setPropertyValue(Shop_Item_Model $oShop_Item, Property_Model $oProperty, mixed $value): void
    {
        $Property_Controller_Value = Property_Controller_Value::factory($oProperty->type);
        $modelName = $Property_Controller_Value->getModelName();

        $oProperty_Values = Core_Entity::factory($modelName);
        $oProperty_Values->queryBuilder()
            ->where('property_id', '=', $oProperty->id)
            ->where('entity_id', '=', $oShop_Item->id);

        $aProperty_Values = $oProperty_Values->findAll();
        $oProperty_Value = $aProperty_Values[0] ?? null;

        if ($oProperty_Value && !$value) {
            $oProperty_Value->delete();
            return;
        }

        if (!$oProperty_Value) {
            $oProperty_Value = Core_Entity::factory($modelName);
            $oProperty_Value->property_id = $oProperty->id;
            $oProperty_Value->entity_id = $oShop_Item->id;
        }

        $oProperty_Value->value = $value;
        $oProperty_Value->save();
    }

    /**
     * Записать значение свойства по тегу
     * @throws Core_Exception
     */
    public static function setPropertyValueByTag(Shop_Item_Model $oShop_Item, string $tagname, mixed $value): void
    {
        if ($oProperty = self::getPropertyByTag($oShop_Item, $tagname)) {
            self:
            self::setPropertyValue($oShop_Item, $oProperty, $value);
        }
    }

    /**
     * Сортировка товаров: "нет в наличии" убираем в конец списка
     * @param Shop_Item_Model[] $aShop_Items - массив товаров
     * @param bool $checkRedirect - проверять карточку-редирект
     * @return Shop_Item_Model[]
     */
    public static function sortByRest(array $aShop_Items, bool $checkRedirect = false): array
    {
        $aNoRest = [];
        $aHasRest = [];
        $aRedirects = [];

        // "Нет в наличии" убираем в конец списка
        foreach ($aShop_Items as $oShop_Item) {

            // Находим товар по ярлыку
            if ($oShop_Item->shortcut_id) {
                $oShop_Item = $oShop_Item->Shop_Item;
                if (!$oShop_Item->active) {
                    continue;
                }
            }

            if ($checkRedirect && KovSpace_ShopItem::propertyByTag($oShop_Item, 'redirect')) {
                $oShop_Item->dataHasRest = true;
                $aRedirects[] = $oShop_Item;
            } else if (($oShop_Item->price == 0 || $oShop_Item->getRest() == 0)) {
                $oShop_Item->dataHasRest = false;
                $aNoRest[] = $oShop_Item;
            } else {
                $oShop_Item->dataHasRest = true;
                $aHasRest[] = $oShop_Item;
            }
        }
        return array_merge($aHasRest, $aNoRest, $aRedirects);
    }
}
