<?php
// Profile: name, username, email, city and university.
$user = require_login();
$uid = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = clean_name(preg_replace('/[^\p{L}\p{M}\s]/u', '', post('name')));
    $username   = preg_replace('/\s+/', '', mb_strtolower(post('username')));
    $email      = preg_replace('/\s+/', '', mb_strtolower(post('email')));
    $university = trim(preg_replace('/\s+/u', ' ', post('university')));

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = t('Enter your full name.');
    }
    if ($msg = validate_username($username, $uid)) {
        $errors['username'] = $msg;
    }
    if (!valid_email($email)) {
        $errors['email'] = t('Enter a valid email address.');
    } elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $uid])->fetch()) {
        $errors['email'] = t('An account with this email already exists. Log in instead.');
    }
    if (mb_strlen($university) > 160) {
        $errors['university'] = t('Keep the university name under 160 characters.');
    }
    $cityId = post('city') === '' ? null : resolve_city();
    $photo = $errors ? null : save_image('avatar');

    if (!$errors) {
        $avatar = $user['avatar'];
        if ($photo || post('avatar_remove') === '1') {
            delete_upload($user['avatar']);
            $avatar = $photo;
        }
        q('UPDATE users SET name = ?, username = ?, email = ?, city_id = ?, university = ?, avatar = ? WHERE id = ?',
            [$name, $username, $email, $cityId, $university !== '' ? $university : null, $avatar, $uid]);
        notice('profile', t('Profile saved.'));
        redirect('profile');
    }
}

render('profile', [
    'title'  => t('Profile'),
    'user'   => $user,
    'cities' => all_cities(),
]);
