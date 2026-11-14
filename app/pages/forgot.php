<?php
// Forgot password: send a one-hour link for choosing a new password.
if (current_user()) {
    redirect('');
}

$sent = false;
$devLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(post('email'));

    // a few requests per visitor every 15 minutes
    $now = time();
    $_SESSION['resets'] = array_values(array_filter($_SESSION['resets'] ?? [], fn($t) => $t > $now - 900));

    if (!valid_email($email)) {
        $errors['email'] = t('Enter a valid email address.');
    } elseif (count($_SESSION['resets']) >= 5) {
        $errors['email'] = t('Too many requests. Wait a few minutes and try again.');
    } else {
        $_SESSION['resets'][] = $now;
        $user = q('SELECT id, name, email FROM users WHERE email = ?', [$email])->fetch();

        // Same answer whether or not the address has an account
        $sent = true;

        $recent = $user && q('SELECT 1 FROM password_resets WHERE user_id = ? AND created_at > NOW() - INTERVAL 1 MINUTE', [$user['id']])->fetch();
        if ($user && !$recent) {
            $token = bin2hex(random_bytes(32));
            q('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
            q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)',
                [$user['id'], hash('sha256', $token)]);

            $link = ORIGIN . url('reset-password?token=' . $token);
            $body = t("Hello %s,\n\nSomeone asked to reset the password for your RusStart account. Open this link to choose a new password:\n\n%s\n\nThe link works for one hour and only once. If you did not ask for this, ignore this email and your password stays the same.\n\nRusStart",
                explode(' ', $user['name'])[0], $link);
            send_mail($user['email'], t('Reset your RusStart password'), $body);

            // On your own PC, emails are saved to a file instead of sent; show the link so it can be tested
            if ($config['debug'] && $config['mail']['driver'] === 'log') {
                $devLink = $link;
            }
        }
    }
}

render('forgot', [
    'title'     => t('Forgot password'),
    'sent'      => $sent,
    'devLink'   => $devLink,
    'bodyClass' => 'is-auth',
]);
