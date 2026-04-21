<?php
// Bootstrap for PHPStan to handle constants or session starts if needed
define('TEST_MODE', true);
if (!isset($_SESSION)) {
    $_SESSION = [];
}

/** @var PDO $pdo */
$pdo = new PDO('sqlite::memory:');

