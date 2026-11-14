<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    remember_forget();
    $_SESSION = [];
    session_regenerate_id(true);
}
redirect('');
