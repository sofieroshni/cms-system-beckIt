<?php
/**
 * Template for TextSection-blokken.
 *
 * @var string                            $title
 * @var string                            $intro
 * @var string                            $listTitle
 * @var string                            $footerText
 * @var array<int, array<string, string>> $items
 * @var string                            $cssVars
 *
 * e() foer nl2br(): escaping foerst, linjeskift bagefter. Bytter man om,
 * bliver de indsatte br-tags selv escapet og vist som synlig tekst.
 */
?>
<section class="block block--textsection"<?= eAttr(['style' => $cssVars]) ?>>
    <div class="textsection__inner">
        <?php if ($title !== ''): ?>
            <h2 class="textsection__title"><?= e($title) ?></h2>
        <?php endif; ?>

        <?php if ($intro !== ''): ?>
            <p class="textsection__intro"><?= nl2br(e($intro)) ?></p>
        <?php endif; ?>

        <?php if ($items !== []): ?>
            <div class="textsection__card">
                <?php if ($listTitle !== ''): ?>
                    <h3 class="textsection__list-title"><?= e($listTitle) ?></h3>
                <?php endif; ?>

                <ul class="textsection__list">
                    <?php foreach ($items as $item): ?>
                        <?php $text = trim((string) ($item['text'] ?? '')); ?>
                        <?php if ($text !== ''): ?>
                            <li><?= e($text) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($footerText !== ''): ?>
            <p class="textsection__footer"><?= nl2br(e($footerText)) ?></p>
        <?php endif; ?>
    </div>
</section>