<?php
/**
 * Template for TextArea-blokken.
 *
 * @var string $title
 * @var string $body
 * @var string $cssVars
 *
 * e() foer nl2br(): escaping foerst, linjeskift bagefter. Bytter man om,
 * bliver de indsatte br-tags selv escapet og vist som synlig tekst.
 */
?>
<section class="block block--textarea"<?= eAttr(['style' => $cssVars]) ?>>
    <div class="textarea__inner">
        <?php if ($title !== ''): ?>
            <h2 class="textarea__title"><?= e($title) ?></h2>
        <?php endif; ?>

        <?php if ($body !== ''): ?>
            <div class="textarea__body"><?= nl2br(e($body)) ?></div>
        <?php endif; ?>
    </div>
</section>