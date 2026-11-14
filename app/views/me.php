<?php partial('account_head', ['tab' => 'articles']) ?>

<section class="section account-body">
    <div class="wrap wrap-narrow">
        <div class="panel-head panel-head-end">
            <a class="btn btn-primary btn-sm" href="<?= url('articles/new') ?>"><?= et('Write an article') ?></a>
        </div>
        <?php if ($articles): ?>
            <ul class="mine-list">
                <?php foreach ($articles as $a): ?>
                    <li>
                        <div>
                            <a class="mine-title" href="<?= url('articles/' . $a['slug']) ?>"><?= e($a['title']) ?></a>
                            <span class="mine-meta"><?= e($a['city']) ?>, <?= e($a['category']) ?>, <?= e(short_date($a['created_at'])) ?>, <?= e(tn((int) $a['views'], 'view')) ?></span>
                        </div>
                        <div class="mine-actions">
                            <a class="btn btn-ghost btn-sm" href="<?= url('articles/' . $a['slug'] . '/edit') ?>"><?= et('Edit') ?></a>
                            <form method="post" action="<?= url('articles/' . $a['slug'] . '/delete') ?>" data-confirm="<?= et('Delete this article? This cannot be undone.') ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-danger btn-sm" type="submit"><?= et('Delete') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php else: ?>
            <div class="empty">
                <h3><?= et('You have not written anything yet.') ?></h3>
                <p><?= et('Your first month in Russia is someone else\'s biggest worry. Share how you handled it.') ?></p>
                <a class="btn btn-primary" href="<?= url('articles/new') ?>"><?= et('Write an article') ?></a>
            </div>
        <?php endif ?>
    </div>
</section>
