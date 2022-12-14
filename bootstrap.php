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

    // Версия HostCMS
    public static function hostcmsUpdateNumber(): int
    {
        return defined('HOSTCMS_UPDATE_NUMBER')
            ? HOSTCMS_UPDATE_NUMBER
            : Core_Entity::factory('Constant')->getByName('HOSTCMS_UPDATE_NUMBER');
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
    // Прерываем исполнение
    static public function stop(Core_Mail $object): Core_Mail
    {
        if (KovSpace_Bootstrap::hostcmsUpdateNumber() < 194) {
            if (!str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/update/')) {
                die();
            }
        }
        return $object;
    }

    // Записываем в лог
    static public function log(string $message): void
    {
        Core_Log::instance()
            ->clear()
            ->notify(FALSE)
            ->write('Core_Mail: ' . $message);
    }

    // Создаем задание для крона
    static public function job(Core_Mail $object): void
    {
        $dir = CMS_FOLDER . 'cron/jobs/mail';
        $file = $dir . '/' . time() . rand(100, 999);
        $content = serialize($object);
        !defined('CHMOD') && define('CHMOD', 0755);
        !defined('CHMOD_FILE') && define('CHMOD_FILE', 0644);
        try {
            Core_File::mkdir($dir, CHMOD, TRUE);
            Core_File::write($file, $content);
        } catch (Exception) {
            static::log('Не удалось создать Mail Job');
        }
    }

    // Метод-перехватчик
    static public function onBeforeSend(Core_Mail $object): ?Core_Mail
    {
        // Появилось в HostCMS 7.0.4 (194)
        $to = method_exists($object, 'getTo')
            ? $object->getTo()
            : KovSpace_Function::getProtectedProperty($object, '_to');

        if (!$to) {
            static::log('Должен быть получатель');
            return static::stop($object);
        }

        // Не уведомляем о таких ошибках
        if (str_contains($object->getSubject(), 'Error: YML /cart')) {
            static::log('Маркет: Адрес не найден');
            return static::stop($object);
        }

        // Не уведомляем о таких ошибках
        if (str_contains($object->getSubject(), 'Неверные данные для аутентификации')) {
            static::log('Неверный логин/пароль');
            return static::stop($object);
        }

        // Предотвращаем спам из ошибок
        if (str_starts_with($object->getSubject(), 'HostCMS')) {

            // Кэш не всегда очищается корректно
            if (str_contains($object->getSubject(), 'unlink')) {
                if (str_contains($object->getSubject(), '/hostcmsfiles/cache/')) {
                    static::log('Cache: Не получилось удалить файл');
                    return static::stop($object);
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
                static::log('Дубль времени');
                return static::stop($object);
            }

            // Оставляем только события в пределах 5 минут
            foreach ($emails as $date => $email) {

                try {
                    $prev = new DateTime($date);
                } catch (Exception) {
                    static::log('Неверное время в ' . $file);
                    return static::stop($object);
                }

                if ($now->getTimestamp() - $prev->getTimestamp() > 5 * 60) {
                    unset($emails[$date]);
                }
            }

            if (in_array($to, $emails)) {
                static::log('Прошло слишком мало времени');
                return static::stop($object);
            }

            $emails = [$nowF => $to] + $emails;
            file_put_contents($file, json_encode($emails, JSON_PRETTY_PRINT));
        }

        // Получаем параметры для текущего сайта
        $aConfig = KovSpace_Function::getProtectedProperty($object, '_config');

        // С какого адреса. Обнуляем, так как иначе может быть спуфинг.
        $from = null;

        if (!$from && isset($aConfig['from'])) {
            $from = $aConfig['from'];
        }

        if (!$from && $object instanceof Core_Mail_Smtp && isset($aConfig['username'])) {
            $from = $aConfig['username'];
        }

        if (!$from) {
            $from = 'noreply@' . $_SERVER['SERVER_NAME'];
        }

        $object->from($from);

        // Имя отправителя
        $senderName = KovSpace_Function::getProtectedProperty($object, '_senderName');

        if (!$senderName) {
            $senderName = $aConfig['sendername'] ?? $_SERVER['SERVER_NAME'];
            $object->senderName($senderName);
        }

        $headers = KovSpace_Function::getProtectedProperty($object, '_headers');

        // Кому отвечаем на письмо
        if (!isset($headers['Reply-To'])) {
            $replyTo = $aConfig['reply-to'] ?? $from;
            $object->header('Reply-To', '<' . $replyTo . '>');
        }

        // SMTP отправляем по крону, если вызов был через HostCMS frontend (index.php)
        // KovSpace_Bootstrap::defineCurrentSite(); для эмуляции фронта
        if (defined('CURRENT_SITE')) {
            if ($object instanceof Core_Mail_Smtp) {
                static::job($object);
                return static::stop($object);
            }
        }

        return null;
    }
}
