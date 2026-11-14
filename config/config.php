<?php
/**
 * Default settings (local XAMPP).
 * To override anything (production database, email), copy
 * config.local.example.php to config.local.php and edit that file.
 * config.local.php is not committed to git.
 */
$config = [
    'app_name' => 'RusStart',

    // The city the project is centred on (slug from the cities table).
    // It is listed first everywhere and highlighted in the hero.
    'main_city' => 'novosibirsk',

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3307,          // XAMPP MySQL was moved to 3307 on this PC
        'name' => 'russtart',
        'user' => 'root',
        'pass' => '',
    ],

    // "Forgot password?" on the login page. Off for now; to turn it on, set true here or in
    // config.local.php, and fill in 'mail' below so the reset emails are really sent.
    'password_reset' => false,

    // Email (password reset links).
    //   'log'  saves emails to storage/mail instead of sending them (for testing on your PC)
    //   'smtp' sends them, e.g. Gmail: host smtp.gmail.com, port 587, secure tls,
    //          user = your Gmail address, pass = an App Password (not your normal password)
    'mail' => [
        'driver'    => 'log',
        'from'      => 'no-reply@russtart.local',
        'from_name' => 'RusStart',
        'smtp'      => ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls', 'user' => '', 'pass' => ''],
    ],

    // Add the demo students, articles and advertisements (database/migrations/0002_demo_content.php).
    // They are added once. To remove them later run:  DELETE FROM users WHERE email LIKE '%@russtart.demo';
    'demo_data' => true,

    // Show detailed PHP errors. On by default only on your own computer (localhost);
    // set it in config.local.php to override.
    'debug' => in_array($_SERVER['HTTP_HOST'] ?? 'localhost', ['localhost', '127.0.0.1'], true)
        || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:'),
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
