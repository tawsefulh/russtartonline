<section class="auth">
    <?php partial('auth_side', ['text' => t('One more step, and you are in.')]) ?>

    <div class="auth-card">
        <h1><?= et('2-step verification') ?></h1>
        <p class="auth-lead"><?= et('Open your authenticator app and enter the 6-digit code for RusStart.') ?></p>

        <form method="post" action="<?= url('login/verify') ?>" novalidate>
            <?= csrf_field() ?>
            <?= field('code', t('6-digit code'), ['required' => true, 'value' => '', 'attrs' => ' inputmode="numeric" autocomplete="one-time-code" maxlength="9" autofocus spellcheck="false" autocapitalize="none"', 'hint' => t('No phone? Enter one of your backup codes instead.')]) ?>
            <button class="btn btn-primary btn-block" type="submit"><?= et('Log in') ?></button>
        </form>

        <p class="auth-switch"><a href="<?= url('login') ?>"><?= et('Back to log in') ?></a></p>
    </div>
</section>
