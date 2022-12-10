<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Bootstrap
{
    public static function init(): void
    {
        if (!defined('SKIP_KOVSPACE_BOOTSTRAP')) {
            self::serverName();
            self::coreMail();
        }
    }

    // Исправление имени сервера
    public static function serverName(): void
    {
        if (!isset($_SERVER['SERVER_NAME'])) {
            $aPath = explode('/www/', CMS_FOLDER);
            if (count($aPath) == 3) {
                $_SERVER['SERVER_NAME'] = $aPath[1];
            } else {
                $oSite_Aliases = Core_Entity::factory('Site_Alias');
                $oSite_Aliases->queryBuilder()
                    ->where('current', '=', 1)
                    ->where('deleted', '=', 0)
                    ->limit(1);
                $aSite_Aliases = $oSite_Aliases->findAll();
                $oSite_Alias = $aSite_Aliases[0] ?? null;
                if ($oSite_Alias) {
                    $_SERVER['SERVER_NAME'] = str_replace('*.', '', $oSite_Alias->name);
                }
            }
        }
    }

    // Получение текущего сайта
    public static function getCurrentSiteId(): int
    {
        $oSite_Alias = Core_Entity::factory('Site_Alias')->findAlias($_SERVER['SERVER_NAME']);
        $oSite = $oSite_Alias->Site;
        return $oSite->id;
    }

    // Определение константы
    public static function defineCurrentSite(): void
    {
        if (!defined('CURRENT_SITE')) {
            define('CURRENT_SITE', static::getCurrentSiteId());
        }
    }

    // Отправка писем
    public static function coreMail(): void
    {
        Core_Event::attach('Core_Mail.onBeforeSend', array('Core_Mail_Observer', 'onBeforeSend'));
    }
}

class Core_Mail_Observer
{
    static public function onBeforeSend(Core_Mail $object): ?Core_Mail
    {
        $log = function (string $message) use ($object) {
            Core_Log::instance()
                ->clear()
                ->notify(FALSE)
                ->write('Core_Mail: ' . $message);
            // Для совместимости с HostCMS 7.0.4 и ниже
            $object->from('')->to('')->recipientName('');
        };

        // Метод появился в HostCMS 7.0.4
        $to = method_exists($object, 'getTo')
            ? $object->getTo()
            : KovSpace_Function::getProtectedProperty($object, '_to');

        // Должен быть получатель
        if (!$to) {
            return $object;
        }

        // Назначаем отправителя по-умолчанию. Настройка [smtp][from] перезапишет.
        if (isset($_SERVER['SERVER_NAME'])) {
            $object
                ->from('noreply@' . $_SERVER['SERVER_NAME'])
                ->senderName('noreply@' . $_SERVER['SERVER_NAME']);
        }

        // Не уведомляем о таких ошибках
        if (str_contains($object->getSubject(), 'Error: YML /cart')) {
            $log('Маркет: Адрес не найден');
            return $object;
        }

        // Предотвращаем спам из ошибок
        if (str_starts_with($object->getSubject(), 'HostCMS')) {

            // Кэш не всегда очищается корректно
            if (str_contains($object->getSubject(), 'unlink')) {
                if (str_contains($object->getSubject(), '/hostcmsfiles/cache/')) {
                    $log('Cache: Не получилось удалить файл');
                    return $object;
                }
            }

            $now = new DateTime('now');
            $nowF = $now->format('Y-m-d H:i:s');
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

        // SMTP отправляем по крону, если вызов был через HostCMS frontend (index.php)
        // KovSpace_Bootstrap::defineCurrentSite(); для эмуляции фронта
        if (defined('CURRENT_SITE')) {
            if ($object instanceof Core_Mail_Smtp) {
                $dir = CMS_FOLDER . 'cron/jobs/mail';
                $file = $dir . '/' . time() . rand(100, 999);
                $content = serialize($object);
                Core_File::mkdir($dir, CHMOD, TRUE);
                Core_File::write($file, $content);
                return $object;
            }
        }

        return null;
    }
}
