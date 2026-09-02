<?php
/**
 * Template for Welcome-blokken.
 *
 * @var string                            $title
 * @var string                            $intro
 * @var string                            $listTitle
 * @var string                            $footerText
 * @var array<int, array<string, string>> $items
 * @var string                            $cssVars
 *
 * nl2br() på brødteksten bevarer redaktørens linjeskift. Rækkefølgen er
 * vigtig: e() escaper FØRST, nl2br() tilføjer <br> BAGEFTER. Byttes de om,
 * ville de indsatte br-tags selv blive escapet og vist som tekst.
 */
?>
<section class="block block--welcome"<?= eAttr(['style' => $cssVars]) ?>>
    <div class="welcome__inner">
        <?php if ($title !== ''): ?>
            <h2 class="welcome__title"><?= e($title) ?></h2>
        <?php endif; ?>

        <?php if ($intro !== ''): ?>
            <p class="welcome__intro"><?= nl2br(e($intro)) ?></p>
        <?php endif; ?>

        <?php if ($items !== []): ?>
            <div class="welcome__card">
                <?php if ($listTitle !== ''): ?>
                    <h3 class="welcome__list-title"><?= e($listTitle) ?></h3>
                <?php endif; ?>

                <ul class="welcome__list">
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
            <p class="welcome__footer"><?= nl2br(e($footerText)) ?></p>
        <?php endif; ?>
    </div>
</section>