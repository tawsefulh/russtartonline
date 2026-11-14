<?php
/** "Formatting tips": what to type, and what readers will see. */
$tips = [
    ['**' . t('bold text') . '**', format_inline('**' . t('bold text') . '**')],
    ['*' . t('italic text') . '*', format_inline('*' . t('italic text') . '*')],
    ['__' . t('underlined text') . '__', format_inline('__' . t('underlined text') . '__')],
    ['## ' . t('Heading'), '<span class="tip-h2">' . et('Heading') . '</span>'],
    ['### ' . t('Small heading'), '<span class="tip-h3">' . et('Small heading') . '</span>'],
    ['- ' . t('item'), '<span class="tip-list">• ' . et('item') . '</span>'],
    ['1. ' . t('first step'), '<span class="tip-list">1. ' . et('first step') . '</span>'],
    ['> ' . t('a quote'), '<span class="tip-quote">' . et('a quote') . '</span>'],
    ['[' . t('link text') . '](https://example.com)', format_inline('[' . t('link text') . '](https://example.com)')],
];
?>
<details class="format-tips">
    <summary><?= et('Formatting tips') ?></summary>
    <p><?= et('Use the buttons above, or type these signs yourself. Leave an empty line to start a new paragraph.') ?></p>
    <table>
        <thead><tr><th><?= et('You type') ?></th><th><?= et('Readers see') ?></th></tr></thead>
        <tbody>
            <?php foreach ($tips as [$typed, $shown]): ?>
                <tr><td><code><?= e($typed) ?></code></td><td><?= $shown ?></td></tr>
            <?php endforeach ?>
        </tbody>
    </table>
</details>
