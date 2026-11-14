<?php partial('account_head', ['tab' => 'profile']) ?>

<section class="section account-body">
    <div class="wrap wrap-narrow">
        <div class="panel">
            <?php partial('notice', ['key' => 'profile']) ?>

            <form method="post" action="<?= url('profile') ?>" enctype="multipart/form-data" novalidate data-check-url="<?= url('check') ?>">
                <?= csrf_field() ?>
                <div class="field photo-field<?= isset($errors['avatar']) ? ' has-error' : '' ?>">
                    <label class="photo-pick" data-avatar-editor>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                        <span class="upload-preview photo-preview"<?= $user['avatar'] ? ' style="background-image:url(' . e(upload_url($user['avatar'])) . ')"' : '' ?>><?php if (!$user['avatar']): ?><b><?= e(initials($user['name'])) ?></b><?php endif ?></span>
                        <span class="upload-text">
                            <strong><?= $user['avatar'] ? et('Change photo') : et('Add a profile photo') ?></strong>
                            <small data-upload-name><?= et('JPG, PNG or WebP, up to 3 MB. You can move and zoom it after choosing.') ?></small>
                        </span>
                    </label>
                    <?php if ($user['avatar']): ?>
                        <button class="btn btn-danger btn-sm photo-remove" type="submit" formaction="<?= url('profile/photo/remove') ?>" formnovalidate
                                data-remove-photo data-confirm-text="<?= et('Remove your profile photo?') ?>" data-add-text="<?= et('Add a profile photo') ?>"><?= et('Remove photo') ?></button>
                    <?php endif ?>
                    <?= field_error('avatar') ?>
                </div>

                <dialog class="photo-editor" aria-labelledby="photo-editor-title">
                    <h2 id="photo-editor-title"><?= et('Adjust your photo') ?></h2>
                    <p><?= et('Drag the photo to move it. Zoom with the slider or your mouse wheel.') ?></p>
                    <div class="crop" tabindex="0" aria-label="<?= et('Photo position. Use the arrow keys to move it.') ?>">
                        <img alt="">
                        <span class="crop-ring" aria-hidden="true"></span>
                    </div>
                    <label class="zoom">
                        <span><?= et('Zoom') ?></span>
                        <input type="range" min="1" max="4" step="0.01" value="1">
                    </label>
                    <div class="photo-editor-actions">
                        <button class="btn btn-ghost" type="button" data-crop-cancel><?= et('Cancel') ?></button>
                        <button class="btn btn-primary" type="button" data-crop-use><?= et('Use photo') ?></button>
                    </div>
                </dialog>
                <?= field('name', t('Full name'), ['required' => true, 'value' => old('name', $user['name']), 'attrs' => ' autocomplete="name" maxlength="100" data-clean="name"', 'hint' => t('Letters and spaces only.')]) ?>
                <?= field('username', t('Username'), ['value' => $user['username'], 'attrs' => ' readonly aria-readonly="true" tabindex="-1"', 'hint' => t('Your username cannot be changed.')]) ?>
                <?= field('email', t('Email'), ['required' => true, 'value' => old('email', $user['email']), 'attrs' => ' autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false" data-check="email"']) ?>
                <?= select_field('city', t('City in Russia'), $cities, ['any' => t('Not set'), 'other' => t('Other city'), 'other_label' => t('City name'), 'selected' => old('city', (string) $user['city_id'])]) ?>
                <?= field('university', t('University'), ['value' => old('university', (string) $user['university']), 'attrs' => ' maxlength="160"', 'hint' => t('Optional.')]) ?>
                <button class="btn btn-primary" type="submit"><?= et('Save profile') ?></button>
            </form>
        </div>
    </div>
</section>
