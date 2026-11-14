<?php
/**
 * Default settings (local XAMPP).
 * To override anything (production database, Google keys), copy
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

    // Google sign-in. Leave empty to hide the Google button.
    // Create keys at https://console.cloud.google.com/apis/credentials
    'google' => [
        'client_id'     => '',
        'client_secret' => '',
    ],

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

    // Show detailed PHP errors (turn off in production)
    'debug' => true,
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
