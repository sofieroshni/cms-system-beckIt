<?php
/**
 * Template for Image-blokken.
 *
 * @var string $src      Faerdig, valideret URL. Tom hvis intet billede.
 * @var string $alt
 * @var string $caption
 * @var string $cssVars
 *
 * <figure> og <figcaption> frem for div og p: billedtekstens tilhoersforhold
 * til billedet staar dermed i selve markup'en og ikke kun visuelt.
 */
?>
<section class="block block--image"<?= eAttr(['style' => $cssVars]) ?>>
    <?php if ($src !== ''): ?>
        <figure class="image__figure">
            <?php /* loading="lazy" udskyder billeder laengere nede paa siden. */ ?>
            <img class="image__img" src="<?= e($src) ?>" alt="<?= e($alt) ?>" loading="lazy">

            <?php if ($caption !== ''): ?>
                <figcaption class="image__caption"><?= e($caption) ?></figcaption>
            <?php endif; ?>
        </figure>
    <?php else: ?>
        <p class="image__empty">Intet billede valgt.</p>
    <?php endif; ?>
</section>