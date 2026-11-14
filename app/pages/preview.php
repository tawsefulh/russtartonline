<?php
// Preview button on the article and advertisement forms: the text as readers will see it.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    not_found();
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
$text = is_string($_POST['text'] ?? null) ? mb_substr($_POST['text'], 0, 30000) : '';
echo format_body(str_replace("\r\n", "\n", $text));
