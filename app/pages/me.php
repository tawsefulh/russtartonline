<?php
$user = require_login();

render('me', [
    'title'    => t('My articles'),
    'user'     => $user,
    'articles' => find_posts('articles', ['user_id' => $user['id']], 200),
]);
