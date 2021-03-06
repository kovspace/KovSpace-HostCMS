<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Function
{
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
}
