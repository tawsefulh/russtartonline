<?php
// Remove the profile photo straight away (the Remove photo button on Profile).
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['avatar']) {
    delete_upload($user['avatar']);
    q('UPDATE users SET avatar = NULL WHERE id = ?', [$user['id']]);
}

// the button normally works in the background; without JavaScript it reloads the page
if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'initials' => initials($user['name'])]);
    exit;
}
notice('profile', t('Photo removed.'));
redirect('profile');
