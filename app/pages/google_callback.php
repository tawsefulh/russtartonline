<?php
// Step 2 of Google sign-in: Google sends the person back here with a code.
function google_fail(string $why = ''): void
{
    global $config;
    flash('error', t('Google sign-in did not finish. Try again.') . ($config['debug'] && $why ? " ($why)" : ''));
    redirect('login');
}

function http_json(string $url, ?array $post = null, array $headers = []): ?array
{
    $payload = $post !== null ? http_build_query($post) : null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        // PHP's built-in HTTPS, for servers where the curl extension is not loaded (XAMPP often)
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $body = @file_get_contents($url, false, stream_context_create(['http' => [
            'method'        => $payload !== null ? 'POST' : 'GET',
            'header'        => implode("\r\n", $headers),
            'content'       => $payload ?? '',
            'timeout'       => 15,
            'ignore_errors' => true,
        ]]));
    }

    $data = is_string($body) ? json_decode($body, true) : null;
    return is_array($data) ? $data : null;
}

$state = $_SESSION['google_state'] ?? '';
unset($_SESSION['google_state']);

if (!google_enabled() || $state === '' || !hash_equals($state, query('state')) || query('code') === '') {
    google_fail(query('error'));
}

$token = http_json('https://oauth2.googleapis.com/token', [
    'code'          => query('code'),
    'client_id'     => $config['google']['client_id'],
    'client_secret' => $config['google']['client_secret'],
    'redirect_uri'  => ORIGIN . base_url('auth/google/callback'),
    'grant_type'    => 'authorization_code',
]);
if (empty($token['access_token'])) {
    google_fail($token['error_description'] ?? 'no token');
}

$profile = http_json('https://openidconnect.googleapis.com/v1/userinfo', null,
    ['Authorization: Bearer ' . $token['access_token']]);
if (empty($profile['sub']) || empty($profile['email']) || empty($profile['email_verified'])) {
    google_fail('no verified email');
}

$email = mb_strtolower($profile['email']);
$user = q('SELECT id, name, google_id FROM users WHERE google_id = ? OR email = ? ORDER BY google_id = ? DESC LIMIT 1',
    [$profile['sub'], $email, $profile['sub']])->fetch();

if ($user) {
    if (!$user['google_id']) {
        q('UPDATE users SET google_id = ? WHERE id = ?', [$profile['sub'], $user['id']]);
    }
    login_user((int) $user['id']);
    flash('success', t('Welcome back, %s. Good to see you again.', explode(' ', $user['name'])[0]));
    redirect_after_login();
}

// New person: make a username from the email
$base = substr(preg_replace('/[^a-z0-9]/', '', strtolower(strstr($email, '@', true))), 0, 24);
$base = strlen($base) >= 4 ? $base : 'student';
$username = $base;
while (q('SELECT 1 FROM users WHERE username = ?', [$username])->fetch()) {
    $username = $base . random_int(100, 9999);
}

q('INSERT INTO users (name, username, email, google_id) VALUES (?, ?, ?, ?)',
    [clean_name($profile['name'] ?? $base), $username, $email, $profile['sub']]);
login_user((int) db()->lastInsertId());
flash('success', t('Welcome to RusStart, %s! Your account is ready. Start by reading about your city or sharing your own story.', explode(' ', clean_name($profile['name'] ?? $base))[0]));
redirect_after_login();
