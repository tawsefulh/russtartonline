<?php
if (current_user()) {
    redirect('');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = clean_name(preg_replace('/[^\p{L}\p{M}\s]/u', '', post('name')));
    $username   = preg_replace('/\s+/', '', mb_strtolower(post('username')));
    $email      = preg_replace('/\s+/', '', mb_strtolower(post('email')));
    $password   = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = t('Enter your full name.');
    }
    if ($msg = validate_username($username)) {
        $errors['username'] = $msg;
    }
    if (!valid_email($email)) {
        $errors['email'] = t('Enter a valid email address.');
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        $errors['email'] = t('An account with this email already exists. Log in instead.');
    }
    if ($msg = password_problem($password)) {
        $errors['password'] = $msg;
    }

    if (!$errors) {
        q('INSERT INTO users (name, username, email, password_hash) VALUES (?, ?, ?, ?)',
            [$name, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        login_user((int) db()->lastInsertId());
        redirect_after_login();
    }
}

render('register', [
    'title'     => t('Create account'),
    'bodyClass' => 'is-auth',
]);
