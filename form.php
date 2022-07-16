<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form Handler
 *
 * @author KovSpace
 * @version 2018-07-28
 * @copyright © 2018 https://kovspace.com/
 */
class KovSpace_Form
{
    public $error;
    public $success;

    public function __construct($informationsystem_id, $email_to = EMAIL_TO, $subject = null)
    {
        $ip = Core_Array::get($_SERVER, 'REMOTE_ADDR');

        $config = Core::$config->get('core_mail');

        if (isset($config['smtp'][CURRENT_SITE]['username'])) {
            $email_from = $config['smtp'][CURRENT_SITE]['username'];
        } elseif (isset($config['smtp']['username'])) {
            $email_from = $config['smtp']['username'];
        } else {
            $email_from = $email_to;
        }

        if ($subject === null) {
            $subject = Core_Entity::factory('Informationsystem', $informationsystem_id)->name;
        }

        if ($subject && Core_Array::getPost('form')) {
            $error = '';

            if (!$error && !Core_Array::getPost('name')) {
                $error = 'Укажите ваше имя';
            }
            if (!$error && !Core_Array::getPost('phone')) {
                $error = 'Укажите ваш телефон';
            }
            if (!$error && Core_Array::getPost('phone') && substr(Core_Array::getPost('phone'), 0, 2) != '+7') {
                $error = 'Неверный телефон';
            }
            if (!$error && !Core_Array::getPost('email')) {
                $error = 'Укажите ваш email';
            }
            if (!$error && Core_Array::getPost('email') && !filter_var(Core_Array::getPost('email'), FILTER_VALIDATE_EMAIL)) {
                $error = 'Неверный email';
            }
            if (!$error && !Core_Array::getPost('comment')) {
                $error = 'Напишите ваше сообщение';
            }
            if (!$error && Core_Array::getPost('url')) {
                $error = 'Обнаружен спам';
            }

            $oLastItems = Core_Entity::factory('Informationsystem_Item');
            $oLastItems->queryBuilder()
                ->where('informationsystem_id', '=', $informationsystem_id)
                ->where('ip', '=', $ip)
                ->limit(1)
                ->clearOrderBy()
                ->orderBy('id', 'DESC');
            $aLastItems = $oLastItems->findAll();

            if ($aLastItems) {
                $oLastItem = $aLastItems[0];
                if (time() < Core_Date::sql2timestamp($oLastItem->datetime) + ADD_COMMENT_DELAY) {
                    $timeDiff = Core_Date::sql2timestamp($oLastItem->datetime) + ADD_COMMENT_DELAY - time();
                    $error = 'Слишком частая отправка сообщений. Попробуйте через ' . $timeDiff . ' секунд';
                }
            }

            if ($error) {
                $this->error = $error;
            } else {
                $message = '<div>Имя: ' . Core_Array::getPost('name') . '</div>';
                $message .= '<div>Телефон: ' . Core_Array::getPost('phone') . '</div>';
                $message .= '<div>Email: ' . Core_Array::getPost('email') . '</div>';
                $message .= '<div>Сообщение: ' . Core_Array::getPost('comment') . '</div>';
                $message .= '<div>---</div>';
                $message .= '<div>IP: <a href="http://ipgeobase.ru/cgi-bin/Search.cgi?address=' . $ip . '">' . $ip . '</a></div>';
                $message .= '<div>Сайт: ' . Core::$url['host'] . '</div>';

                $oInformationsystem_Item = Core_Entity::factory('Informationsystem_Item');
                $oInformationsystem_Item->informationsystem_id = $informationsystem_id;
                $oInformationsystem_Item->name = Core_Array::getPost('name');
                $oInformationsystem_Item->text = $message;
                $oInformationsystem_Item->save();

                $oCore_Mail_Driver = Core_Mail::instance()
                    ->to($email_to)
                    ->from($email_from)
                    ->subject($subject)
                    ->message($message)
                    ->senderName(Core_Array::getPost('name'))
                    ->contentType('text/html')
                    ->send();

                $this->success = 'Форма успешно отправлена!';
            }
        }
    }
}
