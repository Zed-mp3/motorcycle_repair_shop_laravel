<?php
define('LEGACY_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once LEGACY_ROOT . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
