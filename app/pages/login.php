<?php
if (current_user()) {
    redirect('');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = preg_replace('/\s+/', '', mb_strtolower(post('login')));
    $password = $_POST['password'] ?? '';

    $ip = client_ip();
    $failures = q('SELECT
            SUM(login = ?) AS for_login,
            SUM(ip = ?) AS for_ip
        FROM login_failures WHERE created_at > NOW() - INTERVAL 15 MINUTE AND (login = ? OR ip = ?)',
        [$login, $ip, $login, $ip])->fetch();
    $blocked = (int) $failures['for_login'] >= 10 || (int) $failures['for_ip'] >= 30;

    $user = $blocked ? null : q('SELECT id, password_hash, totp_secret FROM users WHERE email = ? OR username = ?', [$login, $login])->fetch();

    if ($user && is_string($password) && password_verify($password, $user['password_hash'])) {
        q('DELETE FROM login_failures WHERE login = ?', [$login]);
        if ($user['totp_secret']) {
            $_SESSION['pending_2fa'] = ['uid' => (int) $user['id'], 'remember' => post('remember') === '1', 'at' => time(), 'tries' => 0];
            redirect('login/verify');
        }
        complete_login((int) $user['id'], post('remember') === '1');
    }

    if ($blocked) {
        $errors['login'] = t('Too many attempts. Wait 15 minutes and try again, or reset your password.');
    } else {
        q('INSERT INTO login_failures (ip, login) VALUES (?, ?)', [$ip, substr($login, 0, 190)]);
        q('DELETE FROM login_failures WHERE created_at < NOW() - INTERVAL 1 DAY');
        $errors['login'] = t('The email, username or password is not correct.');
    }
    usleep(400000); // slow down guessing
}

render('login', ['title' => t('Log in'), 'bodyClass' => 'is-auth', 'passwordReset' => query('reset') === '1']);
