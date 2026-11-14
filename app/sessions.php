<?php
/**
 * Signed-in devices ("sessions"), so members can see where they are logged in
 * and sign out any of them from the Security page.
 */

const SESSION_IDLE_SHORT = 3600;          // a session without "keep me logged in" counts as ended after 1 hour idle
const SESSION_IDLE_LONG = 30 * 86400;     // a remembered one after 30 days

/** Start tracking this browser for the member who just logged in. */
function session_track_start(int $userId): void
{
    if (!empty($_SESSION['sid'])) {
        q('DELETE FROM user_sessions WHERE session_key = ?', [$_SESSION['sid']]);
    }
    $key = bin2hex(random_bytes(16));
    q('INSERT INTO user_sessions (user_id, session_key, ip, user_agent, last_seen_at) VALUES (?, ?, ?, ?, NOW())',
        [$userId, $key, client_ip(), substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    $_SESSION['sid'] = $key;
}

/**
 * Is this browser's session still allowed? False once it was signed out from another device.
 * Also refreshes "last active" every few minutes.
 */
function session_track_check(int $userId): bool
{
    if (empty($_SESSION['sid'])) {                 // logged in before sessions were tracked
        session_track_start($userId);
        return true;
    }
    $row = q('SELECT id, last_seen_at FROM user_sessions WHERE session_key = ? AND user_id = ?', [$_SESSION['sid'], $userId])->fetch();
    if (!$row) {
        return false;
    }
    if (strtotime($row['last_seen_at']) < time() - 300) {
        q('UPDATE user_sessions SET last_seen_at = NOW(), ip = ? WHERE id = ?', [client_ip(), $row['id']]);
    }
    return true;
}

/** Link the "keep me logged in" token to this session, so signing the session out cancels it too. */
function session_track_remember(string $selector): void
{
    if (!empty($_SESSION['sid'])) {
        q('UPDATE user_sessions SET remember_selector = ? WHERE session_key = ?', [$selector, $_SESSION['sid']]);
    }
}

/** Sign out one tracked session (and its "keep me logged in" token). */
function session_track_end(int $sessionId, int $userId): void
{
    $row = q('SELECT remember_selector FROM user_sessions WHERE id = ? AND user_id = ?', [$sessionId, $userId])->fetch();
    if ($row && $row['remember_selector']) {
        q('DELETE FROM remember_tokens WHERE selector = ?', [$row['remember_selector']]);
    }
    q('DELETE FROM user_sessions WHERE id = ? AND user_id = ?', [$sessionId, $userId]);
}

/** Sign out every session of a member except this browser's. */
function session_track_end_others(int $userId): void
{
    foreach (q('SELECT id FROM user_sessions WHERE user_id = ? AND session_key <> ?', [$userId, $_SESSION['sid'] ?? ''])->fetchAll() as $row) {
        session_track_end((int) $row['id'], $userId);
    }
}

function session_track_list(int $userId): array
{
    q('DELETE FROM user_sessions WHERE user_id = ? AND (
          (remember_selector IS NULL AND last_seen_at < NOW() - INTERVAL ' . SESSION_IDLE_SHORT . ' SECOND)
       OR (last_seen_at < NOW() - INTERVAL ' . SESSION_IDLE_LONG . ' SECOND))', [$userId]);
    return q('SELECT id, session_key, ip, user_agent, created_at, last_seen_at FROM user_sessions
              WHERE user_id = ? ORDER BY session_key = ? DESC, last_seen_at DESC', [$userId, $_SESSION['sid'] ?? ''])->fetchAll();
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** "Chrome on Windows" from a browser's user-agent text. */
function describe_device(string $agent): string
{
    $browsers = ['YaBrowser' => 'Yandex Browser', 'Edg/' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox',
                 'SamsungBrowser' => 'Samsung Internet', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'];
    $systems = ['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iPhone', 'iPad' => 'iPad',
                'Mac OS X' => 'macOS', 'CrOS' => 'ChromeOS', 'Linux' => 'Linux'];
    $browser = t('Unknown browser');
    foreach ($browsers as $needle => $name) {
        if (str_contains($agent, $needle)) {
            $browser = $name;
            break;
        }
    }
    foreach ($systems as $needle => $name) {
        if (str_contains($agent, $needle)) {
            return t('%s on %s', $browser, $name);
        }
    }
    return $browser;
}
