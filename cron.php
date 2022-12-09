<?php

require_once(dirname(__FILE__, 3) . '/bootstrap.php');

function mailJobs(): void
{
    $dir = CMS_FOLDER . 'cron/jobs/mail';
    $files = KovSpace_Function::getFilesInDir($dir);
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $oCore_Mail = unserialize($content);
        if ($oCore_Mail instanceof Core_Mail_Smtp) {
            if (!$oCore_Mail->send()->getStatus()) {
                (new Core_Mail_Sendmail)
                    ->to($oCore_Mail->getTo())
                    ->from($oCore_Mail->getFrom())
                    ->subject($oCore_Mail->getSubject())
                    ->message($oCore_Mail->getMessage())
                    ->send();
            }
        } elseif ($oCore_Mail instanceof Core_Mail) {
            $oCore_Mail->send();
        }
        unlink($file);
    }
}

// Start
mailJobs();
