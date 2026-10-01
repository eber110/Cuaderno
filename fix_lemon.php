<?php

$file = 'App/Controllers/LemonSqueezyControllers.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $content = str_replace('SecurityModule::sanitizeArray(\Base\Module\SecurityModule::sanitizeArray($_GET ?? []))', '\Base\Module\SecurityModule::sanitizeArray($_GET ?? [])', $content);
    $content = str_replace('SecurityModule::sanitizeArray(\Base\Module\SecurityModule::sanitizeArray($_POST ?? []))', '\Base\Module\SecurityModule::sanitizeArray($_POST ?? [])', $content);
    file_put_contents($file, $content);
}
