<?php if ($message = take_notice($key)): ?>
    <p class="notice" role="status"><?= e($message) ?></p>
<?php endif ?>
