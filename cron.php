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
            $result = $oCore_Mail->send();
            $status = $result instanceof Core_Mail_Smtp
                ? $result->getStatus()
                : false;
            if (!$status) {
                $contentType = KovSpace_Function::getProtectedProperty($oCore_Mail, '_contentType');
                $headers = KovSpace_Function::getProtectedProperty($oCore_Mail, '_headers');
                $message = method_exists($oCore_Mail, 'getMessage')
                    ? $oCore_Mail->getMessage()
                    : KovSpace_Function::getProtectedProperty($oCore_Mail, '_message');
                Core_Mail::instance('sendmail')
                    ->to($oCore_Mail->getTo())
                    ->from($oCore_Mail->getFrom())
                    ->subject($oCore_Mail->getSubject())
                    ->message($message)
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
