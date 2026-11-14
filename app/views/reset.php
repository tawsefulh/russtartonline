<section class="auth">
    <?php partial('auth_side', ['text' => t('A fresh password, and you are back with the students who got there first.')]) ?>

    <div class="auth-card">
        <?php if ($reset): ?>
            <h1><?= et('Choose a new password') ?></h1>
            <p class="auth-lead"><?= et('Hello %s. Pick a password you have not used here before.', explode(' ', $reset['name'])[0]) ?></p>
            <form method="post" action="<?= url('reset-password') ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <?= field('password', t('New password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="new-password" minlength="8" data-password-rules']) ?>
                <?php partial('password_rules') ?>
                <button class="btn btn-primary btn-block" type="submit"><?= et('Save new password') ?></button>
            </form>
        <?php else: ?>
            <h1><?= et('This link no longer works') ?></h1>
            <p class="auth-lead"><?= et('Reset links work for one hour and only once. Ask for a new one and use the latest email.') ?></p>
            <a class="btn btn-primary btn-block" href="<?= url('forgot-password') ?>"><?= et('Send a new link') ?></a>
            <p class="auth-switch"><a href="<?= url('login') ?>"><?= et('Back to log in') ?></a></p>
        <?php endif ?>
    </div>
</section>
