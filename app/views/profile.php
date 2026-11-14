<?php partial('account_head', ['tab' => 'profile']) ?>

<section class="section account-body">
    <div class="wrap wrap-narrow">
        <div class="panel">
            <?php partial('notice', ['key' => 'profile']) ?>

            <form method="post" action="<?= url('profile') ?>" enctype="multipart/form-data" novalidate data-check-url="<?= url('check') ?>">
                <?= csrf_field() ?>
                <div class="field photo-field<?= isset($errors['avatar']) ? ' has-error' : '' ?>">
                    <label class="photo-pick" data-upload>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                        <span class="upload-preview photo-preview"<?= $user['avatar'] ? ' style="background-image:url(' . e(upload_url($user['avatar'])) . ')"' : '' ?>><?php if (!$user['avatar']): ?><b><?= e(initials($user['name'])) ?></b><?php endif ?></span>
                        <span class="upload-text">
                            <strong><?= $user['avatar'] ? et('Change photo') : et('Add a profile photo') ?></strong>
                            <small data-upload-name><?= et('JPG, PNG or WebP, up to 3 MB. A square photo of your face works best.') ?></small>
                        </span>
                    </label>
                    <?php if ($user['avatar']): ?>
                        <label class="check photo-remove">
                            <input type="checkbox" name="avatar_remove" value="1">
                            <span class="check-box" aria-hidden="true"></span>
                            <?= et('Remove photo') ?>
                        </label>
                    <?php endif ?>
                    <?= field_error('avatar') ?>
                </div>
                <?= field('name', t('Full name'), ['required' => true, 'value' => old('name', $user['name']), 'attrs' => ' autocomplete="name" maxlength="100" data-clean="name"', 'hint' => t('Letters and spaces only.')]) ?>
                <?= field('username', t('Username'), ['required' => true, 'value' => old('username', $user['username']), 'attrs' => ' autocomplete="username" maxlength="30" autocapitalize="none" spellcheck="false" data-check="username"', 'hint' => t('At least 4 characters. Small letters and numbers only.')]) ?>
                <?= field('email', t('Email'), ['required' => true, 'value' => old('email', $user['email']), 'attrs' => ' autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false" data-check="email"']) ?>
                <?= select_field('city', t('City in Russia'), $cities, ['any' => t('Not set'), 'other' => t('Other city'), 'other_label' => t('City name'), 'selected' => old('city', (string) $user['city_id'])]) ?>
                <?= field('university', t('University'), ['value' => old('university', (string) $user['university']), 'attrs' => ' maxlength="160"', 'hint' => t('Optional.')]) ?>
                <button class="btn btn-primary" type="submit"><?= et('Save profile') ?></button>
            </form>
        </div>
    </div>
</section>
