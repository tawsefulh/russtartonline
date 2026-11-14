<?php
// Second step of logging in for members with 2-step verification.
$pending = $_SESSION['pending_2fa'] ?? null;
if (!$pending || $pending['at'] < time() - 600) {
    unset($_SESSION['pending_2fa']);
    redirect('login');
}

$user = q('SELECT id, totp_secret, totp_last_step FROM users WHERE id = ?', [$pending['uid']])->fetch();
if (!$user || !$user['totp_secret']) {
    unset($_SESSION['pending_2fa']);
    redirect('login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = post('code');
    $step = totp_match($user['totp_secret'], $code, $user['totp_last_step'] !== null ? (int) $user['totp_last_step'] : null);

    if ($step !== null) {
        q('UPDATE users SET totp_last_step = ? WHERE id = ?', [$step, $user['id']]);
    }
    if ($step !== null || backup_code_use((int) $user['id'], $code)) {
        unset($_SESSION['pending_2fa']);
        complete_login((int) $user['id'], !empty($pending['remember']));
    }

    $_SESSION['pending_2fa']['tries'] = ($pending['tries'] ?? 0) + 1;
    if ($_SESSION['pending_2fa']['tries'] >= 5) {
        unset($_SESSION['pending_2fa']);
        redirect('login');
    }
    $errors['code'] = t('That code is not right. Enter the 6 digits your app shows now, or one of your backup codes.');
    usleep(400000);
}

render('login_verify', ['title' => t('2-step verification'), 'bodyClass' => 'is-auth']);
