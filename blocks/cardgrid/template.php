<?php
/**
 * Template for CardGrid-blokken.
 *
 * @var string                            $title
 * @var array<int, array<string, string>> $cards
 * @var string                            $cssVars
 */
?>
<section class="block block--cardgrid"<?= eAttr(['style' => $cssVars]) ?>>
    <?php if ($title !== ''): ?>
        <h2 class="cardgrid__title"><?= e($title) ?></h2>
    <?php endif; ?>

    <?php if ($cards !== []): ?>
        <ul class="cardgrid__grid">
            <?php foreach ($cards as $card): ?>
                <li class="cardgrid__card">
                    <?php if ($card['text'] !== ''): ?>
                        <p class="cardgrid__text"><?= nl2br(e($card['text'])) ?></p>
                    <?php endif; ?>

                    <?php if ($card['buttonLabel'] !== ''): ?>
                        <a class="cardgrid__button" href="<?= e($card['buttonHref']) ?>">
                            <?= e($card['buttonLabel']) ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>