<?php
/** Heading of an account page. Needs: $tab (articles | ads | profile | security) */
$heads = [
    'articles' => [t('My articles'), t('Everything you have written, newest first.')],
    'ads'      => [t('My advertisements'), t('What you are offering, and where buyers can reach you.')],
    'profile'  => [t('Profile'), t('Your name and university appear next to everything you post.')],
    'security' => [t('Security'), t('Your password, 2-step verification, the devices you are logged in on, and your account.')],
];
[$heading, $lead] = $heads[$tab];
?>
<section class="page-head">
    <div class="wrap wrap-narrow">
        <h1><?= e($heading) ?></h1>
        <p class="page-lead"><?= e($lead) ?></p>
    </div>
</section>
