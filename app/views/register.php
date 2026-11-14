<section class="auth">
    <?php partial('auth_side', ['text' => t('Every guide here was written by a student who arrived the same way you will.')]) ?>

    <div class="auth-card">
        <h1><?= et('Create account') ?></h1>
        <p class="auth-lead"><?= et('Join the students who share what they learned about life in Russia, and help the next one arrive ready.') ?></p>

        <?php partial('google_button') ?>

        <form method="post" action="<?= url('register') ?>" novalidate data-check-url="<?= url('check') ?>">
            <?= csrf_field() ?>
            <?= field('name', t('Full name'), ['required' => true, 'attrs' => ' autocomplete="name" maxlength="100"']) ?>
            <?= field('username', t('Username'), ['required' => true, 'attrs' => ' autocomplete="username" maxlength="30" autocapitalize="none" spellcheck="false" data-check="username"', 'hint' => t('At least 4 characters. Small letters and numbers only.')]) ?>
            <?= field('email', t('Email'), ['type' => 'email', 'required' => true, 'attrs' => ' autocomplete="email" spellcheck="false" data-check="email" autocapitalize="none"']) ?>
            <?= field('password', t('Password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="new-password" minlength="8" data-password-rules']) ?>
            <?php partial('password_rules') ?>
            <button class="btn btn-primary btn-block" type="submit"><?= et('Create account') ?></button>
        </form>

        <p class="auth-switch"><?= et('Already a member?') ?> <a href="<?= url('login') ?>"><?= et('Log in') ?></a></p>
    </div>
</section>
