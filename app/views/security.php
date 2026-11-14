<?php
partial('account_head', ['tab' => 'security']);
$on = (bool) $user['totp_secret'];
// the forms for turning 2-step off or replacing codes stay open after a wrong password
$openOff = $formAction === 'twostep_off';
$openCodes = $formAction === 'twostep_codes';
$openDeactivate = $formAction === 'deactivate';
?>
<section class="section account-body">
    <div class="wrap wrap-narrow">

        <!-- password -->
        <div class="panel" id="password">
            <h2><?= et('Change password') ?></h2>
            <p class="panel-lead"><?= et('After the change, every other device is signed out.') ?></p>
            <?php partial('notice', ['key' => 'password']) ?>
            <form method="post" action="<?= url('security') ?>#password" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <?= field('pw_current', t('Current password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="current-password"']) ?>
                <?= field('pw_new', t('New password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="new-password" minlength="8" data-password-rules']) ?>
                <?php partial('password_rules') ?>
                <button class="btn btn-primary" type="submit"><?= et('Change password') ?></button>
            </form>
        </div>

        <!-- 2-step verification -->
        <div class="panel" id="two-step">
            <div class="panel-title">
                <h2><?= et('2-step verification') ?></h2>
                <span class="state<?= $on ? ' is-on' : '' ?>"><?= $on ? et('On') : et('Off') ?></span>
            </div>
            <p class="panel-lead"><?= et('Besides your password, log in with a 6-digit code from an authenticator app on your phone, such as Google Authenticator or Microsoft Authenticator.') ?></p>
            <?php partial('notice', ['key' => 'two-step']) ?>

            <?php if ($backupCodes): ?>
                <div class="codes-box">
                    <h3><?= et('Save your backup codes') ?></h3>
                    <p><?= et('If you lose your phone, each of these codes lets you log in once. They are shown only now: write them down or save them somewhere safe.') ?></p>
                    <ol class="backup-codes">
                        <?php foreach ($backupCodes as $code): ?><li><?= e($code) ?></li><?php endforeach ?>
                    </ol>
                    <button class="btn btn-ghost btn-sm" type="button" data-copy="<?= e(implode("\n", $backupCodes)) ?>" data-copied="<?= et('Copied') ?>"><?= et('Copy codes') ?></button>
                </div>
            <?php endif ?>

            <?php if ($on): ?>
                <p class="panel-fact"><?= et('On since %s.', long_date($user['totp_enabled_at'])) ?> <?= et('Backup codes left: %d.', $codesLeft) ?></p>
                <div class="panel-actions">
                    <details class="reveal"<?= $openCodes ? ' open' : '' ?>>
                        <summary class="btn btn-ghost btn-sm"><?= et('New backup codes') ?></summary>
                        <form method="post" action="<?= url('security') ?>#two-step" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="twostep_codes">
                            <?= field('tc_password', t('Current password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="current-password"']) ?>
                            <button class="btn btn-navy btn-sm" type="submit"><?= et('Create new codes') ?></button>
                        </form>
                    </details>
                    <details class="reveal"<?= $openOff ? ' open' : '' ?>>
                        <summary class="btn btn-danger btn-sm"><?= et('Turn off') ?></summary>
                        <form method="post" action="<?= url('security') ?>#two-step" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="twostep_off">
                            <?= field('tf_password', t('Current password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="current-password"']) ?>
                            <button class="btn btn-danger btn-sm" type="submit"><?= et('Turn off 2-step verification') ?></button>
                        </form>
                    </details>
                </div>
            <?php elseif ($setupSecret): ?>
                <ol class="setup-steps">
                    <li>
                        <strong><?= et('Scan this QR code with your authenticator app.') ?></strong>
                        <div class="qr-row">
                            <div class="qr" data-qr="<?= e($setupUri) ?>" role="img" aria-label="<?= et('QR code for your authenticator app') ?>"></div>
                            <p><?= et('Cannot scan it? Add an account in the app by hand and type this key:') ?>
                                <code class="secret"><?= e(implode(' ', str_split($setupSecret, 4))) ?></code></p>
                        </div>
                    </li>
                    <li>
                        <strong><?= et('Enter the 6-digit code the app shows.') ?></strong>
                        <form method="post" action="<?= url('security') ?>#two-step" novalidate class="code-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="twostep_confirm">
                            <?= field('tf_code', t('6-digit code'), ['required' => true, 'value' => '', 'attrs' => ' inputmode="numeric" autocomplete="one-time-code" maxlength="7" data-clean="code"']) ?>
                            <div class="panel-actions">
                                <button class="btn btn-primary" type="submit"><?= et('Turn on') ?></button>
                            </div>
                        </form>
                        <form method="post" action="<?= url('security') ?>#two-step">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="twostep_cancel">
                            <button class="link-button" type="submit"><?= et('Cancel') ?></button>
                        </form>
                    </li>
                </ol>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
            <?php else: ?>
                <form method="post" action="<?= url('security') ?>#two-step">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="twostep_start">
                    <button class="btn btn-navy" type="submit"><?= et('Set up 2-step verification') ?></button>
                </form>
            <?php endif ?>
        </div>

        <!-- sessions -->
        <div class="panel" id="sessions">
            <h2><?= et('Sessions') ?></h2>
            <p class="panel-lead"><?= et('Devices where you are logged in. If you do not recognise one, sign it out and change your password.') ?></p>
            <?php partial('notice', ['key' => 'sessions']) ?>
            <ul class="session-list">
                <?php foreach ($sessions as $s): ?>
                    <?php $isCurrent = $s['session_key'] === ($_SESSION['sid'] ?? ''); ?>
                    <li>
                        <span class="session-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <?php if (preg_match('/Android|iPhone|Mobile/', $s['user_agent'])): ?>
                                    <path d="M8 2.5h8a1.5 1.5 0 0 1 1.5 1.5v16a1.5 1.5 0 0 1-1.5 1.5H8A1.5 1.5 0 0 1 6.5 20V4A1.5 1.5 0 0 1 8 2.5zM11 18.5h2"/>
                                <?php else: ?>
                                    <path d="M3.5 5h17v11h-17zM8 20h8M12 16v4"/>
                                <?php endif ?>
                            </svg>
                        </span>
                        <div class="session-text">
                            <strong><?= e(describe_device($s['user_agent'])) ?></strong>
                            <?php if ($isCurrent): ?><span class="state is-on"><?= et('This device') ?></span><?php endif ?>
                            <span class="session-meta">
                                <?= e($s['ip'] === '::1' || $s['ip'] === '127.0.0.1' ? t('This computer') : $s['ip']) ?>,
                                <?= $isCurrent || strtotime($s['last_seen_at']) > time() - 300 ? et('active now') : et('last active %s', short_date($s['last_seen_at'])) ?>,
                                <?= et('signed in %s', short_date($s['created_at'])) ?>
                            </span>
                        </div>
                        <?php if (!$isCurrent): ?>
                            <form method="post" action="<?= url('security') ?>#sessions">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="session_end">
                                <input type="hidden" name="session" value="<?= (int) $s['id'] ?>">
                                <button class="btn btn-ghost btn-sm" type="submit"><?= et('Sign out') ?></button>
                            </form>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
            <?php if (count($sessions) > 1): ?>
                <form method="post" action="<?= url('security') ?>#sessions" data-confirm="<?= et('Sign out every other device?') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="sessions_others">
                    <button class="btn btn-navy btn-sm" type="submit"><?= et('Sign out all other devices') ?></button>
                </form>
            <?php endif ?>
        </div>

        <!-- deactivate -->
        <div class="panel panel-danger" id="deactivate">
            <h2><?= et('Deactivate account') ?></h2>
            <p class="panel-lead"><?= et('Your profile, articles and advertisements are hidden from everyone. Nothing is deleted: log in again any time and everything comes back.') ?></p>
            <details class="reveal"<?= $openDeactivate ? ' open' : '' ?>>
                <summary class="btn btn-danger"><?= et('Deactivate account') ?></summary>
                <form method="post" action="<?= url('security') ?>#deactivate" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <?= field('da_password', t('Current password'), ['type' => 'password', 'required' => true, 'attrs' => ' autocomplete="current-password"']) ?>
                    <div class="field<?= isset($errors['da_confirm']) ? ' has-error' : '' ?>">
                        <label class="check">
                            <input type="checkbox" name="da_confirm" value="1">
                            <span class="check-box" aria-hidden="true"></span>
                            <?= et('I understand my posts will be hidden until I log in again.') ?>
                        </label>
                        <?= field_error('da_confirm') ?>
                    </div>
                    <button class="btn btn-danger" type="submit"><?= et('Deactivate my account') ?></button>
                </form>
            </details>
        </div>

    </div>
</section>
