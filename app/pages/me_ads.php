<?php
$user = require_login();

render('me_ads', [
    'title' => t('My advertisements'),
    'user'  => $user,
    'ads'   => find_posts('ads', ['user_id' => $user['id']], 200),
]);
