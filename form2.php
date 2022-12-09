<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form Handler
 *
 * @author KovSpace
 * @version 2021-09-27
 * @copyright © 2018 https://kovspace.com/
 */

class KovSpace_Form2
{
    public $error = null;
    public $success = 'Спасибо! Ваша заявка получена.';
    public $oForm;
    public $aForm_Fields = [];

    public function __construct($form_id)
    {
        $this->oForm = Core_Entity::factory('Form', $form_id);
        $oForm_Fields = $this->oForm->Form_Fields;
        $this->aForm_Fields = $oForm_Fields->findAll();

        // Отправка формы
        if (Core_Array::getPost('form_id') == $this->oForm->id) {

            // Spam protection
            if (Core_Array::getPost('url')) {
                $this->error = 'Обнаружен спам';
            }

            if (!$this->error && Core_Array::getPost('phone') && substr(Core_Array::getPost('phone'), 0, 2) != '+7') {
                $this->error = 'Неверный телефон';
            }

            if (!$this->error && Core_Array::getPost('email') && !filter_var(Core_Array::getPost('email'), FILTER_VALIDATE_EMAIL)) {
                $this->error = 'Неверный email';
            }

            if (!$this->error) {
                $oForm_Fill = Core_Entity::factory('Form_Fill');
                $oForm_Fill->form_id = $this->oForm->id;
                $oForm_Fill->ip = Core_Array::get($_SERVER, 'REMOTE_ADDR');
                $oForm_Fill->datetime = date('Y-m-d H:i:s');
                $oForm_Fill->save();

                $message = '';

                foreach ($this->aForm_Fields as $oForm_Field) {
                    if ($value = Core_Array::getPost($oForm_Field->name)) {
                        $oForm_Fill_Field = Core_Entity::factory('Form_Fill_Field');
                        $oForm_Fill_Field->form_fill_id = $oForm_Fill->id;
                        $oForm_Fill_Field->form_field_id = $oForm_Field->id;
                        $oForm_Fill_Field->value = $value;
                        $oForm_Fill_Field->save();
                        $message .= '<div>' . $oForm_Field->caption . ': ' . $value . '</div>';
                    }
                }

                $oCore_Mail_Driver = Core_Mail::instance()
                    ->to($this->oForm->email)
                    ->subject($this->oForm->email_subject)
                    ->message($message)
                    ->contentType('text/html')
                    ->send();

                header('Location: ?success=1');
                die();
            }
        }
    }

    public function show()
    { ?>


    <?php if (Core_Array::getGet('success')): ?>
        <p><?= $this->success ?></p>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="form_id" value="<?= $this->oForm->id ?>">
            <input type="hidden" name="url">
            <div class="row">
                <?php foreach ($this->aForm_Fields as $oForm_Field): ?>
                    <div class="mb-3 col-lg-6">
                        <label for="<?= $oForm_Field->name ?>" class="form-label">
                            <?= $oForm_Field->caption ?><?php if ($oForm_Field->obligatory): ?><sup>*</sup><?php endif ?>
                        </label>
                        <input id="<?= $oForm_Field->name ?>" type="text" name="<?= $oForm_Field->name ?>" class="form-control" <?php if ($oForm_Field->obligatory) {
                            echo 'required';
                        } ?> value="<?= Core_Array::getPost($oForm_Field->name) ?>">
                        <div class="small text-muted"><?= $oForm_Field->description ?></div>
                    </div>
                <?php endforeach ?>
            </div>

            <?php if ($this->error): ?>
                <div class="my-3 text-danger"><?= $this->error ?></div>
            <?php endif ?>

            <button type="submit" class="btn btn-primary"><?= $this->oForm->button_value ?></button>
        </form>
     <?php endif ?>

    <?php }
}
