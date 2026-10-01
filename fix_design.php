<?php

$file = 'App/Controllers/DesignControllers.php';
$content = file_get_contents($file);

// Fix session usage
$content = preg_replace('/\\$_SESSION\["user"\]\s*\?\?\s*\[\]/', '\Base\Module\Session::user_session_show() ?: []', $content);
$content = preg_replace('/\\$_SESSION\["user"\]\s*\?\?\s*false/', '\Base\Module\Session::user_session_show() ?: false', $content);

// Fix $_GET / $_POST mixed usage
$content = preg_replace('/\\$_GET\["start"\]\s*\?\?\s*\\$_POST\["start"\]\s*\?\?\s*0/', '\Base\Module\SecurityModule::get("start", \Base\Module\SecurityModule::post("start", 0))', $content);
$content = preg_replace('/\\$_GET\["duration"\]\s*\?\?\s*\\$_POST\["duration"\]\s*\?\?\s*20/', '\Base\Module\SecurityModule::get("duration", \Base\Module\SecurityModule::post("duration", 20))', $content);

// Remove orderShare
$content = preg_replace('/(?s)public static function orderShare\(\)\s*:\s*array\s*\{.*?\n\s*\}/', '', $content);

file_put_contents($file, $content);
echo "Done";
