<?php
const REMEMBER_COOKIE = 'russtart_remember';
const REMEMBER_DAYS = 30;

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (empty($_SESSION['uid']) && !empty($_COOKIE[REMEMBER_COOKIE])) {
            remember_login();
        }
        if (!empty($_SESSION['uid'])) {
            $user = q('SELECT u.*, ' . name_sql('c') . ' AS city_name, c.slug AS city_slug
                       FROM users u LEFT JOIN cities c ON c.id = u.city_id
                       WHERE u.id = ?', [$_SESSION['uid']])->fetch() ?: null;
            // signed out everywhere else after a password change
            if ($user && ($_SESSION['pw'] ?? '') !== password_marker($user['password_hash'])) {
                $user = null;
            }
            if ($user && $user['deactivated_at']) {
                $user = null;
            }
            // signed out from another device on the Security page
            if ($user && !session_track_check((int) $user['id'])) {
                $user = null;
            }
            if (!$user) {
                unset($_SESSION['uid'], $_SESSION['pw'], $_SESSION['sid']);
            }
        }
    }
    return $user;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    $_SESSION['pw'] = password_marker(q('SELECT password_hash FROM users WHERE id = ?', [$id])->fetchColumn() ?: null);
    session_track_start($id);
}

/** Short fingerprint of the stored password: when the password changes, older sessions stop working. */
function password_marker(?string $hash): string
{
    return $hash ? substr(hash('sha256', $hash), 0, 16) : '';
}

/** Finish logging in: start the session, remember the device if asked, bring a deactivated account back. */
function complete_login(int $id, bool $remember): void
{
    q('UPDATE users SET deactivated_at = NULL WHERE id = ?', [$id]);
    login_user($id);
    if ($remember) {
        remember_issue($id);
    }
    redirect_after_login();
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login');
    }
    return $u;
}

/** Send the person back to where they were heading before login. */
function redirect_after_login(): void
{
    $to = $_SESSION['after_login'] ?? null;
    unset($_SESSION['after_login']);
    if (is_string($to) && str_starts_with($to, BASE_URL . '/') && !preg_match('#^/[/\\\\]#', $to)) {
        header('Location: ' . $to);
        exit;
    }
    redirect('');
}

function validate_username(string $username, ?int $ignoreId = null): ?string
{
    if (!preg_match('/^[a-z0-9]{4,30}$/', $username)) {
        return t('Use at least 4 characters: small letters and numbers only.');
    }
    $taken = q('SELECT id FROM users WHERE username = ? AND id <> ?', [$username, $ignoreId ?? 0])->fetch();
    return $taken ? t('That username is taken. Try another.') : null;
}

/* ---------- keep me logged in ----------
   The cookie holds "selector:validator". Only a hash of the validator is stored,
   and the pair is replaced every time it is used. */

function remember_set_cookie(string $value, int $expires): void
{
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => BASE_URL . '/',
        'httponly' => true,
        'secure'   => IS_HTTPS,
        'samesite' => 'Lax',
    ]);
}

function remember_issue(int $userId): void
{
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    q('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL ' . REMEMBER_DAYS . ' DAY)',
        [$userId, $selector, hash('sha256', $validator)]);
    remember_set_cookie("$selector:$validator", time() + REMEMBER_DAYS * 86400);
    session_track_remember($selector);
}

function remember_login(): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m)) {
        remember_set_cookie('', time() - 3600);
        return;
    }
    $row = q('SELECT id, user_id, validator_hash FROM remember_tokens WHERE selector = ? AND expires_at > NOW()', [$m[1]])->fetch();
    if ($row) {
        q('DELETE FROM remember_tokens WHERE id = ?', [$row['id']]);
    }
    if ($row && hash_equals($row['validator_hash'], hash('sha256', $m[2]))) {
        q('DELETE FROM user_sessions WHERE remember_selector = ?', [$m[1]]);
        login_user((int) $row['user_id']);
        remember_issue((int) $row['user_id']);
        return;
    }
    remember_set_cookie('', time() - 3600);
}

function remember_forget(): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (is_string($cookie) && preg_match('/^([a-f0-9]{24}):/', $cookie, $m)) {
        q('DELETE FROM remember_tokens WHERE selector = ?', [$m[1]]);
    }
    remember_set_cookie('', time() - 3600);
}

/** A real-looking address: something@domain.tld */
function valid_email(string $email): bool
{
    return strlen($email) <= 190
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && preg_match('/@[^@\s]+\.[a-z]{2,}$/i', $email) === 1;
}

/** New passwords: at least 8 characters with one small letter, one capital letter and one number. */
function password_problem(string $password): ?string
{
    if (mb_strlen($password) < 8 || !preg_match('/\p{Ll}/u', $password) || !preg_match('/\p{Lu}/u', $password) || !preg_match('/\d/', $password)) {
        return t('Use at least 8 characters, with at least one small letter, one capital letter and one number.');
    }
    return null;
}
