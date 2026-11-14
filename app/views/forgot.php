<section class="auth">
    <?php partial('auth_side', ['text' => t('It happens to everyone. You will be back in a minute.')]) ?>

    <div class="auth-card">
        <?php if ($sent): ?>
            <h1><?= et('Check your email') ?></h1>
            <p class="auth-lead"><?= et('If an account uses that address, a link to choose a new password is on its way. It works for one hour.') ?></p>
            <?php if ($devLink): ?>
                <div class="dev-note">
                    <strong><?= et('Email is not set up on this computer yet.') ?></strong>
                    <?= et('The email was saved to storage/mail instead. To test, open the link:') ?>
                    <a href="<?= e($devLink) ?>"><?= et('Choose a new password') ?></a>
                </div>
            <?php endif ?>
            <a class="btn btn-ghost btn-block" href="<?= url('login') ?>"><?= et('Back to log in') ?></a>
        <?php else: ?>
            <h1><?= et('Forgot password?') ?></h1>
            <p class="auth-lead"><?= et('Enter the email you signed up with and we will send you a link to choose a new password.') ?></p>
            <form method="post" action="<?= url('forgot-password') ?>" novalidate>
                <?= csrf_field() ?>
                <?= field('email', t('Email'), ['type' => 'text', 'required' => true, 'attrs' => ' autocomplete="email" inputmode="email" spellcheck="false" autocapitalize="none" data-clean="email"']) ?>
                <button class="btn btn-primary btn-block" type="submit"><?= et('Send reset link') ?></button>
            </form>
            <p class="auth-switch"><?= et('Remembered it?') ?> <a href="<?= url('login') ?>"><?= et('Log in') ?></a></p>
        <?php endif ?>
    </div>
</section>
