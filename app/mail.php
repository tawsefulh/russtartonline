<?php
/**
 * Sending email. Set the way it is sent in config ('mail' => 'driver'):
 *   log  - save each email as a text file in storage/mail (for testing on your PC)
 *   smtp - send through an SMTP server, for example Gmail with an app password
 *   mail - use PHP's built-in mail()
 */
function send_mail(string $to, string $subject, string $body): bool
{
    global $config;
    $m = $config['mail'];

    switch ($m['driver']) {
        case 'smtp':
            return smtp_send($m, $to, $subject, $body);
        case 'mail':
            $headers = 'From: ' . mail_from($m) . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
            return mail($to, mail_subject($subject), $body, $headers);
        default:
            $dir = ROOT . '/storage/mail';
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                return false;
            }
            $file = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';
            return (bool) file_put_contents($file, "To: $to\nSubject: $subject\n\n$body\n");
    }
}

function mail_from(array $m): string
{
    return mail_subject($m['from_name']) . ' <' . $m['from'] . '>';
}

/** Header text that may contain non-English letters. */
function mail_subject(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

/** Minimal SMTP client: TLS (587) or SSL (465), AUTH LOGIN, plain-text UTF-8 body. */
function smtp_send(array $m, string $to, string $subject, string $body): bool
{
    $s = $m['smtp'];
    $host = ($s['secure'] === 'ssl' ? 'ssl://' : '') . $s['host'];
    $sock = @stream_socket_client("$host:{$s['port']}", $errno, $errstr, 15);
    if (!$sock) {
        error_log("SMTP connect failed: $errstr");
        return false;
    }
    stream_set_timeout($sock, 15);

    $read = function () use ($sock): string {
        $all = '';
        while (($line = fgets($sock, 515)) !== false) {
            $all .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        return $all;
    };
    $say = function (string $cmd, array $okCodes) use ($sock, $read): bool {
        if ($cmd !== '') {
            fwrite($sock, $cmd . "\r\n");
        }
        $reply = $read();
        if (!in_array((int) substr($reply, 0, 3), $okCodes, true)) {
            error_log('SMTP error after "' . (str_starts_with($cmd, 'AUTH') ? 'AUTH' : strtok($cmd, ' ')) . '": ' . trim($reply));
            return false;
        }
        return true;
    };

    $me = parse_url(ORIGIN, PHP_URL_HOST) ?: 'localhost';
    $ok = $say('', [220]) && $say("EHLO $me", [250]);
    if ($ok && $s['secure'] === 'tls') {
        $ok = $say('STARTTLS', [220])
            && stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
            && $say("EHLO $me", [250]);
    }
    if ($ok && $s['user'] !== '') {
        $ok = $say('AUTH LOGIN', [334]) && $say(base64_encode($s['user']), [334]) && $say(base64_encode($s['pass']), [235]);
    }
    $ok = $ok && $say("MAIL FROM:<{$m['from']}>", [250]) && $say("RCPT TO:<$to>", [250, 251]) && $say('DATA', [354]);

    if ($ok) {
        $headers = [
            'Date: ' . date('r'),
            'From: ' . mail_from($m),
            'To: <' . $to . '>',
            'Subject: ' . mail_subject($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $me . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        fwrite($sock, implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n"));
        $ok = $say('.', [250]);
    }
    fwrite($sock, "QUIT\r\n");
    fclose($sock);
    return $ok;
}
