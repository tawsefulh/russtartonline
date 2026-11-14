<section class="auth">
    <?php partial('auth_side', ['text' => t('Good to see you again. Someone is reading what you wrote right now.')]) ?>

    <div class="auth-card">
        <h1><?= et('Log in') ?></h1>
        <p class="auth-lead"><?= et('Welcome back. Your articles, ads and city are right where you left them.') ?></p>
        <?php if (!empty($passwordReset)): ?>
            <p class="notice" role="status"><?= et('Password changed. Log in with your new password.') ?></p>
        <?php endif ?>

        <form method="post" action="<?= url('login') ?>" novalidate>
            <?= csrf_field() ?>
            <?= field('login', t('Email or username'), ['required' => true, 'attrs' => ' autocomplete="username" autocapitalize="none" spellcheck="false" data-clean="email"']) ?>
            <?= field('password', t('Password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="current-password"']) ?>
            <div class="login-row">
                <label class="check">
                    <input type="checkbox" name="remember" value="1"<?= post('remember') === '1' ? ' checked' : '' ?>>
                    <span class="check-box" aria-hidden="true"></span>
                    <?= et('Keep me logged in') ?>
                </label>
                <a href="<?= url('forgot-password') ?>"><?= et('Forgot password?') ?></a>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?= et('Log in') ?></button>
        </form>

        <p class="auth-switch"><?= et('New here?') ?> <a href="<?= url('register') ?>"><?= et('Create account') ?></a></p>
    </div>
</section>
