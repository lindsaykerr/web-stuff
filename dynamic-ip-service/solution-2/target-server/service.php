<?php
/**
 * This file simple demonstrates that the check-service.php works as intended.
 */
require_once 'check-service.php';


$service = ServiceStatus::check('loc1');

if ($service) {
    header("HTTP/1.1 200 OK");
    echo "Service is running on " . $service['ip'] . ":" . $service['port'] . "\n";
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Service is no longer running\n";
}
