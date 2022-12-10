<?php
const SKIP_KOVSPACE_BOOTSTRAP = true;
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
                $contentType = KovSpace_Function::getProtectedProperty($oCore_Mail, '_contentType');
                $headers = KovSpace_Function::getProtectedProperty($oCore_Mail, '_headers');
                Core_Mail::instance('sendmail')
                    ->to($oCore_Mail->getTo())
                    ->from($oCore_Mail->getFrom())
                    ->subject($oCore_Mail->getSubject())
                    ->message($oCore_Mail->getMessage())
                    ->contentType($contentType)
                    ->header('Reply-To', $headers['Reply-To'] ?? $oCore_Mail->getFrom())
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
