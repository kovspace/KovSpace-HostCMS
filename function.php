<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Function
{
    // Shop Item Property Values by Tag Name
    public static function getShopItemPropertyValuesByTagName($oShop_Item, $tagname): array
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
                            'file' => $oValue->file,
                            'file_small' => $oValue->file_small,
                        ];
                    } else {
                        $aValues[] = $oValue->value;
                    }
                }
            }
		}

        return $aValues ?? [];
    }

    // Shop Item Property Value by Tag Name
    public static function getShopItemPropertyValueByTagName($oShop_Item, $tagname)
    {
        return self::getShopItemPropertyValuesByTagName($oShop_Item, $tagname)[0] ?? null;
    }

    // Item Property Value
    public static function getItemPropertyValue($oItem, $propertyId)
    {
        $aProperties = $oItem->getPropertyValues(false, array($propertyId));
        if ($aProperties) {
            $oProperty = $aProperties[0];
            return $oProperty->value;
        }
    }

    // Change GET params
    public static function urlParam($param, $value)
    {
        $url_parts = parse_url($_SERVER['REQUEST_URI']);
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $params);
        }
        $params[$param] = $value;
        $url_parts['query'] = http_build_query($params);
        if ($url_parts['query']) {
            return $url_parts['path'] . '?' . $url_parts['query'];
        } else {
            return $url_parts['path'];
        }
    }

    // Redirect
    public static function redirect($url)
    {
        header('Location:' . $url);
        die();
    }

    // Url Param Redirect
    public static function urlParamRedirect($param, $value)
    {
        $url = self::urlParam($param, $value);
        self::redirect($url);
    }

    // Remove old sessions
    public static function removeOldSessions()
    {
        // Empty sessions
        Core_QueryBuilder::delete('sessions')
            ->where('time + maxlifetime', '<', time())
            ->where('value', '=', '')
            ->execute();

        // Older than 1 year
        Core_QueryBuilder::delete('sessions')
            ->where('time', '<', time() - 31556926)
            ->execute();
    }

    // Get Active and Sorted Items
    public static function getSortedItems($object, $sortField = 'sorting', $sortDirection = 'asc'): array
    {
        $object->queryBuilder()
            ->where('active', '=', 1)
            ->orderBy($sortField, $sortDirection);

        return $object->findAll();
    }

    public static function getShopGroups($oShop): array
    {
        $oShop_Groups = $oShop->Shop_Groups;
        $oShop_Groups->queryBuilder()
            ->where('active', '=', 1)
            ->where('parent_id', '=', 0)
            ->orderBy('sorting', 'asc');
        return $oShop_Groups->findAll();
    }
}
