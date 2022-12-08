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
    static public function onBeforeSend(Core_Mail $object): ?Core_Mail
    {
        $log = function (string $message) {
            Core_Log::instance()->clear()->write('Core_Mail: ' . $message);
        };

        if (isset($_SERVER['SERVER_NAME'])) {
            $object->from('noreply@'.$_SERVER['SERVER_NAME']);
        }

        if (str_contains($object->getSubject(), 'Error: YML /cart')) {
            $log('Маркет: Адрес не найден');
            return $object;
        }

        if (str_starts_with($object->getSubject(), 'HostCMS')) {
            $now = new DateTime('now');
            $nowF = $now->format('Y-m-d H:i:s');
            $to = KovSpace_Function::getProtectedProperty($object, '_to');
            $file = CMS_FOLDER . 'hostcmsfiles/logs/emails.json';
            $content = file_exists($file)
                ? file_get_contents($file)
                : '';
            $emails = json_decode($content, true) ?? [];

            if (isset($emails[$nowF])) {
                $log('Дубль времени');
                return $object;
            }

            // Оставляем только события в пределах 5 минут
            foreach ($emails as $date => $email) {
                $prev = new DateTime($date);
                if ($now->getTimestamp() - $prev->getTimestamp() > 5 * 60) {
                    unset($emails[$date]);
                }
            }

            if (in_array($to, $emails)) {
                $log('Прошло слишком мало времени');
                return $object;
            }

            $emails = [$nowF => $to] + $emails;
            file_put_contents($file, json_encode($emails, JSON_PRETTY_PRINT));
        }

        return null;
    }
}