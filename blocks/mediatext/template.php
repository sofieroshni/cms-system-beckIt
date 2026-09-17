<?php
/**
 * Template for MediaText-blokken.
 *
 * @var string $image     Faerdig, valideret URL. Tom hvis intet billede.
 * @var string $imageAlt
 * @var string $title
 * @var string $body
 * @var string $caption
 * @var string $sideClass
 * @var string $cssVars
 */
?>
<section class="block block--mediatext <?= e($sideClass) ?>"<?= eAttr(['style' => $cssVars]) ?>>
    <div class="mediatext__inner">
        <?php if ($image !== ''): ?>
            <div class="mediatext__media">
                <img src="<?= e($image) ?>" alt="<?= e($imageAlt) ?>" loading="lazy">
            </div>
        <?php endif; ?>

        <div class="mediatext__body">
            <?php if ($title !== ''): ?>
                <h2 class="mediatext__title"><?= e($title) ?></h2>
            <?php endif; ?>

            <?php if ($body !== ''): ?>
                <p class="mediatext__text"><?= nl2br(e($body)) ?></p>
            <?php endif; ?>

            <?php if ($caption !== ''): ?>
                <p class="mediatext__caption"><?= e($caption) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>