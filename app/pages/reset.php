<?php
// Choose a new password from the emailed link.
$token = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('token') : query('token');

$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $reset = q('SELECT r.id, r.user_id, u.name FROM password_resets r JOIN users u ON u.id = r.user_id
                WHERE r.token_hash = ? AND r.expires_at > NOW()', [hash('sha256', $token)])->fetch() ?: null;
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (strlen($password) < 8) {
        $errors['password'] = t('Use at least 8 characters.');
    } else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        // the link is spent, and every "keep me logged in" device has to log in again
        q('DELETE FROM password_resets WHERE user_id = ?', [$reset['user_id']]);
        q('DELETE FROM remember_tokens WHERE user_id = ?', [$reset['user_id']]);
        login_user((int) $reset['user_id']);
        flash('success', t('Password changed. Welcome back, %s.', explode(' ', $reset['name'])[0]));
        redirect('');
    }
}

render('reset', [
    'title'     => t('Choose a new password'),
    'reset'     => $reset,
    'token'     => $token,
    'bodyClass' => 'is-auth',
]);
