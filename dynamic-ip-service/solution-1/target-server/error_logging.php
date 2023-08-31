<?php

define("LOG_FILE_LOCATION", ".iperror");

function log_err($message) {
    $file = fopen(LOG_FILE_LOCATION, "a");
    fwrite($file, $message . "\n\r");
    fclose($file);
}