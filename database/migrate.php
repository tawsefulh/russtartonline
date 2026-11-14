<?php
/**
 * Run database migrations by hand:  php database/migrate.php
 * (They also run on their own on the first page visit after new migration files appear.)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT', dirname(__DIR__));
$config = require ROOT . '/config/config.php';
require ROOT . '/app/db.php';
require ROOT . '/app/helpers.php';
require ROOT . '/app/migrate.php';

$ran = run_migrations(true);
echo $ran
    ? 'Ran: ' . implode(', ', $ran) . PHP_EOL
    : 'Nothing to run: the database is up to date.' . PHP_EOL;
