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
    static public function onBeforeSend(Core_Mail $object): void
    {
        $clear = function (Core_Mail $object, string $message) {
            $object->from('')->to('')->recipientName('');
            Core_Log::instance()->clear()->write('Core_Mail: ' . $message);
        };

        if (isset($_SERVER['SERVER_NAME'])) {
            $object->from('noreply@'.$_SERVER['SERVER_NAME']);
        }

        if (str_starts_with($object->getSubject(), 'Error: YML /cart')) {
            $clear($object, 'Маркет: Адрес не найден');
            return;
        }

        if (str_starts_with($object->getSubject(), 'HostCMS')) {
            $now = new DateTime('now');
            $nowF = $now->format('Y-m-d H:i:s');
            $to = KovSpace_Function::getProtectedProperty($object, '_to');
            $file = CMS_FOLDER . 'hostcmsfiles/logs/emails.json';
            $emails = json_decode(@file_get_contents($file), true) ?? [];

            if (isset($emails[$nowF])) {
                $clear($object, 'Дубль времени');
                return;
            }

            // Оставляем только события в пределах 5 минут
            foreach ($emails as $date => $email) {
                $prev = new DateTime($date);
                if ($now->getTimestamp() - $prev->getTimestamp() > 5 * 60) {
                    unset($emails[$date]);
                }
            }

            if (in_array($to, $emails)) {
                $clear($object, 'Прошло слишком мало времени');
                return;
            }

            $emails = [$nowF => $to] + $emails;
            file_put_contents($file, json_encode($emails, JSON_PRETTY_PRINT));
        }
    }
}