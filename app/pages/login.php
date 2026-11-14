<?php
if (current_user()) {
    redirect('');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = preg_replace('/\s+/', '', mb_strtolower(post('login')));
    $password = $_POST['password'] ?? '';

    $user = q('SELECT id, password_hash, totp_secret FROM users WHERE email = ? OR username = ?', [$login, $login])->fetch();

    if ($user && is_string($password) && password_verify($password, $user['password_hash'])) {
        if ($user['totp_secret']) {
            $_SESSION['pending_2fa'] = ['uid' => (int) $user['id'], 'remember' => post('remember') === '1', 'at' => time(), 'tries' => 0];
            redirect('login/verify');
        }
        complete_login((int) $user['id'], post('remember') === '1');
    }

    $errors['login'] = t('The email, username or password is not correct.');
    usleep(400000); // slow down guessing
}

render('login', ['title' => t('Log in'), 'bodyClass' => 'is-auth', 'passwordReset' => query('reset') === '1']);
