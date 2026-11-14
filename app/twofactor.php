<?php
/**
 * 2-step verification with an authenticator app (TOTP, RFC 6238: 6 digits, 30 seconds, SHA-1),
 * plus one-time backup codes for when the phone is not at hand.
 */

const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function totp_new_secret(): string
{
    $secret = '';
    for ($i = 0; $i < 32; $i++) {                         // 160 bits
        $secret .= BASE32_ALPHABET[random_int(0, 31)];
    }
    return $secret;
}

function base32_decode(string $text): string
{
    $bits = '';
    foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text))) as $char) {
        $bits .= str_pad(decbin(strpos(BASE32_ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $out .= chr(bindec($byte));
        }
    }
    return $out;
}

/** The 6-digit code for one 30-second time step. */
function totp_at(string $secret, int $step): string
{
    $hash = hash_hmac('sha1', pack('J', $step), base32_decode($secret), true);
    $o = ord($hash[19]) & 0x0f;
    $n = ((ord($hash[$o]) & 0x7f) << 24) | (ord($hash[$o + 1]) << 16) | (ord($hash[$o + 2]) << 8) | ord($hash[$o + 3]);
    return str_pad((string) ($n % 1000000), 6, '0', STR_PAD_LEFT);
}

/**
 * Check a code against now and the 30 seconds either side (phone clocks drift).
 * Returns the matching time step, or null. A step at or before $after is refused, so a code works once.
 */
function totp_match(string $secret, string $code, ?int $after = null): ?int
{
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6) {
        return null;
    }
    $now = intdiv(time(), 30);
    foreach ([0, -1, 1] as $drift) {
        $step = $now + $drift;
        if (($after === null || $step > $after) && hash_equals(totp_at($secret, $step), $code)) {
            return $step;
        }
    }
    return null;
}

/** Address the authenticator app reads from the QR code. */
function totp_uri(string $secret, string $account): string
{
    return 'otpauth://totp/' . rawurlencode('RusStart:' . $account)
        . '?secret=' . $secret . '&issuer=RusStart&digits=6&period=30';
}

/** Replace a member's backup codes with 8 new ones; returns them for showing once. */
function backup_codes_create(int $userId): array
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';            // no 0/o, 1/l/i look-alikes
    q('DELETE FROM user_backup_codes WHERE user_id = ?', [$userId]);
    $codes = [];
    for ($i = 0; $i < 8; $i++) {
        $raw = '';
        for ($j = 0; $j < 8; $j++) {
            $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        q('INSERT INTO user_backup_codes (user_id, code_hash) VALUES (?, ?)', [$userId, hash('sha256', $raw)]);
        $codes[] = substr($raw, 0, 4) . '-' . substr($raw, 4);
    }
    return $codes;
}

/** Use up one backup code. */
function backup_code_use(int $userId, string $code): bool
{
    $raw = preg_replace('/[^a-z0-9]/', '', strtolower($code));
    if (strlen($raw) !== 8) {
        return false;
    }
    $row = q('SELECT id FROM user_backup_codes WHERE user_id = ? AND code_hash = ? AND used_at IS NULL',
        [$userId, hash('sha256', $raw)])->fetch();
    if (!$row) {
        return false;
    }
    q('UPDATE user_backup_codes SET used_at = NOW() WHERE id = ?', [$row['id']]);
    return true;
}

function backup_codes_left(int $userId): int
{
    return (int) q('SELECT COUNT(*) FROM user_backup_codes WHERE user_id = ? AND used_at IS NULL', [$userId])->fetchColumn();
}
