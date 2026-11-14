<?php
// Shown once, right after an account is deactivated.
if (empty($_SESSION['deactivated'])) {
    redirect('');
}
unset($_SESSION['deactivated']);

render('deactivated', ['title' => t('Account deactivated'), 'bodyClass' => 'is-auth']);
