<?php
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $error_message = date('[Y-m-d H:i:s] ') . "Error: [$errno] $errstr in $errfile on line $errline" . PHP_EOL;
    error_log($error_message, 3, __DIR__ . '/logs/error.log');

    if (ini_get('display_errors')) {
        echo "<p>خطایی رخ داده است. لطفا بعدا دوباره تلاش کنید.</p>";
    }
}

set_error_handler("custom_error_handler");
