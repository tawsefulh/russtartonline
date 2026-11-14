<?php
/**
 * Live check for the sign-up form: is this username or email free?
 * GET /check?field=username|email&value=...
 * Answers JSON: {"ok": true|false, "message": "..."} in the current language.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Keep it to a sensible number of checks per visitor
$now = time();
$_SESSION['checks'] = array_values(array_filter($_SESSION['checks'] ?? [], fn($t) => $t > $now - 60));
if (count($_SESSION['checks']) >= 60) {
    http_response_code(429);
    echo json_encode(['ok' => null, 'message' => '']);
    exit;
}
$_SESSION['checks'][] = $now;

$field = query('field');
$value = mb_strtolower(query('value'));

// A signed-in person's own username and email count as free
$me = current_user();
$ignore = $me ? (int) $me['id'] : null;

if ($field === 'username') {
    $problem = validate_username($value, $ignore);
    $answer = $problem
        ? ['ok' => false, 'message' => $problem]
        : ['ok' => true, 'message' => t('Username is available.')];
} elseif ($field === 'email') {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 190) {
        $answer = ['ok' => false, 'message' => t('Enter a valid email address.')];
    } elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$value, $ignore ?? 0])->fetch()) {
        $answer = ['ok' => false, 'message' => t('An account with this email already exists. Log in instead.')];
    } else {
        $answer = ['ok' => true, 'message' => t('You can use this email.')];
    }
} else {
    http_response_code(400);
    $answer = ['ok' => null, 'message' => ''];
}

echo json_encode($answer, JSON_UNESCAPED_UNICODE);
