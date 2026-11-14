<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_SESSION['sid'])) {
        q('DELETE FROM user_sessions WHERE session_key = ?', [$_SESSION['sid']]);
    }
    remember_forget();
    $_SESSION = [];
    session_regenerate_id(true);
}
redirect('');
