<?php
// Security: change password, 2-step verification, deactivate account.
$user = require_login();
$uid = (int) $user['id'];

/** Every sensitive change asks for the current password first. */
function current_password_ok(array $user, string $field): bool
{
    global $errors;
    $password = $_POST[$field] ?? '';
    if (!is_string($password) || !password_verify($password, $user['password_hash'])) {
        $errors[$field] = t('The current password is not correct.');
        usleep(300000);
        return false;
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch (post('action')) {
        case 'password':
            $new = is_string($_POST['pw_new'] ?? null) ? $_POST['pw_new'] : '';
            $ok = current_password_ok($user, 'pw_current');
            if ($msg = password_problem($new)) {
                $errors['pw_new'] = $msg;
                $ok = false;
            } elseif ($ok && password_verify($new, $user['password_hash'])) {
                $errors['pw_new'] = t('Choose a password different from the current one.');
                $ok = false;
            }
            if ($ok) {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
                // other devices are signed out; this one stays signed in
                $remembered = !empty($_COOKIE[REMEMBER_COOKIE]);
                q('DELETE FROM remember_tokens WHERE user_id = ?', [$uid]);
                q('DELETE FROM user_sessions WHERE user_id = ?', [$uid]);
                login_user($uid);
                if ($remembered) {
                    remember_issue($uid);
                }
                notice('password', t('Password changed. Other devices have been signed out.'));
                redirect('security#password');
            }
            break;

        case 'twostep_start':
            if (!$user['totp_secret']) {
                $_SESSION['totp_setup'] = totp_new_secret();
            }
            redirect('security#two-step');

        case 'twostep_cancel':
            unset($_SESSION['totp_setup']);
            redirect('security#two-step');

        case 'twostep_confirm':
            $secret = $_SESSION['totp_setup'] ?? '';
            if ($secret === '' || $user['totp_secret']) {
                redirect('security#two-step');
            }
            $step = totp_match($secret, post('tf_code'));
            if ($step === null) {
                $errors['tf_code'] = t('That code is not right. Enter the 6 digits your app shows now.');
                break;
            }
            q('UPDATE users SET totp_secret = ?, totp_enabled_at = NOW(), totp_last_step = ? WHERE id = ?', [$secret, $step, $uid]);
            unset($_SESSION['totp_setup']);
            $_SESSION['backup_codes'] = backup_codes_create($uid);
            notice('two-step', t('2-step verification is on.'));
            redirect('security#two-step');

        case 'twostep_codes':
            if ($user['totp_secret'] && current_password_ok($user, 'tc_password')) {
                $_SESSION['backup_codes'] = backup_codes_create($uid);
                notice('two-step', t('New backup codes created. The old ones no longer work.'));
                redirect('security#two-step');
            }
            break;

        case 'twostep_off':
            if ($user['totp_secret'] && current_password_ok($user, 'tf_password')) {
                q('UPDATE users SET totp_secret = NULL, totp_enabled_at = NULL, totp_last_step = NULL WHERE id = ?', [$uid]);
                q('DELETE FROM user_backup_codes WHERE user_id = ?', [$uid]);
                notice('two-step', t('2-step verification is off.'));
                redirect('security#two-step');
            }
            break;

        case 'session_end':
            $sessionId = (int) post('session');
            $current = q('SELECT id FROM user_sessions WHERE session_key = ?', [$_SESSION['sid'] ?? ''])->fetchColumn();
            if ($sessionId && $sessionId !== (int) $current) {
                session_track_end($sessionId, $uid);
                notice('sessions', t('That device has been signed out.'));
            }
            redirect('security#sessions');

        case 'sessions_others':
            session_track_end_others($uid);
            notice('sessions', t('All other devices have been signed out.'));
            redirect('security#sessions');

        case 'deactivate':
            $ok = current_password_ok($user, 'da_password');
            if (post('da_confirm') !== '1') {
                $errors['da_confirm'] = t('Tick the box to confirm.');
                $ok = false;
            }
            if ($ok) {
                q('UPDATE users SET deactivated_at = NOW() WHERE id = ?', [$uid]);
                q('DELETE FROM remember_tokens WHERE user_id = ?', [$uid]);
                q('DELETE FROM user_sessions WHERE user_id = ?', [$uid]);
                remember_forget();
                $_SESSION = [];
                session_regenerate_id(true);
                $_SESSION['deactivated'] = 1;
                redirect('account-deactivated');
            }
            break;
    }
}

// shown once, right after they are created
$backupCodes = $_SESSION['backup_codes'] ?? null;
unset($_SESSION['backup_codes']);

$setupSecret = !$user['totp_secret'] ? ($_SESSION['totp_setup'] ?? null) : null;

render('security', [
    'title'       => t('Security'),
    'user'        => $user,
    'setupSecret' => $setupSecret,
    'setupUri'    => $setupSecret ? totp_uri($setupSecret, $user['email']) : null,
    'backupCodes' => $backupCodes,
    'codesLeft'   => $user['totp_secret'] ? backup_codes_left($uid) : 0,
    'formAction'  => post('action'),
    'sessions'    => session_track_list($uid),
]);
