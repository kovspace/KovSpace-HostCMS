<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Bootstrap
{
    public static function init(): void
    {
        self::serverName();
        self::coreMail();
    }

    // Исправление имени сервера
    public static function serverName(): void
    {
        if (!isset($_SERVER['SERVER_NAME'])) {
            $oSite_Aliases = Core_Entity::factory('Site_Alias');
            $oSite_Aliases->queryBuilder()
                ->where('current', '=', 1)
                ->where('deleted', '=', 0)
                ->limit(1);
            $aSite_Aliases = $oSite_Aliases->findAll();
            $oSite_Alias = $aSite_Aliases[0] ?? null;
            if ($oSite_Alias) {
                $_SERVER['SERVER_NAME'] = $oSite_Alias->name;
            }
        }
    }

    // Mail sender
    public static function coreMail(): void
    {
        Core_Event::attach('Core_Mail.onBeforeSend', array('Core_Mail_Observer', 'onBeforeSend'));
    }
}

class Core_Mail_Observer
{
    static public function onBeforeSend($object, $args)
    {
        if (isset($_SERVER['SERVER_NAME'])) {
            $object->from('noreply@'.$_SERVER['SERVER_NAME']);
        }
    }
}