<?php
/** Formatting buttons above a long text box, and Write / Preview switch. Used inside <div class="editor" data-editor>. */
$buttons = [
    ['bold', t('Bold'), 'Ctrl+B', '<b>B</b>'],
    ['italic', t('Italic'), 'Ctrl+I', '<i>I</i>'],
    ['underline', t('Underline'), 'Ctrl+U', '<u>U</u>'],
    ['heading', t('Heading'), '', '<b>H</b>'],
    ['ul', t('Bulleted list'), '', '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="4" cy="5" r="1.4"/><circle cx="4" cy="10" r="1.4"/><circle cx="4" cy="15" r="1.4"/><path d="M8 5h9M8 10h9M8 15h9"/></svg>'],
    ['ol', t('Numbered list'), '', '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8 5h9M8 10h9M8 15h9"/><text x="1.5" y="7" font-size="5.5">1</text><text x="1.5" y="12" font-size="5.5">2</text><text x="1.5" y="17" font-size="5.5">3</text></svg>'],
    ['quote', t('Quote'), '', '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 4v12M8 6h9M8 10h9M8 14h6"/></svg>'],
    ['link', t('Link'), '', '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8.5 11.5a3.5 3.5 0 0 0 5 0l2.5-2.5a3.5 3.5 0 0 0-5-5l-1 1M11.5 8.5a3.5 3.5 0 0 0-5 0L4 11a3.5 3.5 0 0 0 5 5l1-1"/></svg>'],
];
?>
<div class="editor-bar">
    <div class="editor-tools" role="toolbar" aria-label="<?= et('Formatting') ?>">
        <?php foreach ($buttons as [$action, $label, $keys, $icon]): ?>
            <button type="button" class="editor-btn" data-format="<?= $action ?>" title="<?= e($label . ($keys ? " ($keys)" : '')) ?>" aria-label="<?= e($label) ?>"><?= $icon ?></button>
        <?php endforeach ?>
    </div>
    <div class="editor-modes" role="tablist" aria-label="<?= et('Write or preview') ?>">
        <button type="button" role="tab" aria-selected="true" data-editor-mode="write"><?= et('Write') ?></button>
        <button type="button" role="tab" aria-selected="false" data-editor-mode="preview"><?= et('Preview') ?></button>
    </div>
</div>
