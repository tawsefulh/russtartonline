<?php partial('account_head', ['tab' => 'ads']) ?>

<section class="section account-body">
    <div class="wrap wrap-narrow">
        <div class="panel-head panel-head-end">
            <a class="btn btn-primary btn-sm" href="<?= url('market/new') ?>"><?= et('Post an ad') ?></a>
        </div>
        <?php if ($ads): ?>
            <ul class="mine-list">
                <?php foreach ($ads as $ad): ?>
                    <li>
                        <a class="mine-thumb" href="<?= url('market/' . $ad['slug']) ?>" tabindex="-1" aria-hidden="true">
                            <?php if ($ad['image']): ?>
                                <img src="<?= e(upload_url($ad['image'])) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span><?= e(mb_strtoupper(mb_substr($ad['category'], 0, 1))) ?></span>
                            <?php endif ?>
                        </a>
                        <div>
                            <a class="mine-title" href="<?= url('market/' . $ad['slug']) ?>"><?= e($ad['title']) ?></a>
                            <span class="mine-price"><?= e(price_label($ad['price'])) ?></span>
                            <span class="mine-meta"><?= e($ad['city']) ?>, <?= e($ad['category']) ?>, <?= e(short_date($ad['created_at'])) ?></span>
                        </div>
                        <div class="mine-actions">
                            <a class="btn btn-ghost btn-sm" href="<?= url('market/' . $ad['slug'] . '/edit') ?>"><?= et('Edit') ?></a>
                            <form method="post" action="<?= url('market/' . $ad['slug'] . '/delete') ?>" data-confirm="<?= et('Delete this advertisement? This cannot be undone.') ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-danger btn-sm" type="submit"><?= et('Delete') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php else: ?>
            <div class="empty">
                <h3><?= et('You have no advertisements.') ?></h3>
                <p><?= et('Selling a heater, textbooks or a bicycle before you leave? Newcomers are looking for exactly that.') ?></p>
                <a class="btn btn-primary" href="<?= url('market/new') ?>"><?= et('Post an ad') ?></a>
            </div>
        <?php endif ?>
    </div>
</section>
