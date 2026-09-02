<?php
/**
 * Template for Hero-blokken.
 *
 * Variabler fra HeroBlock::render():
 * @var string $title
 * @var string $address
 * @var string $phone
 * @var string $bgImage  Færdig, valideret URL.
 * @var string $cssVars  CSS-variabler til style-attributten.
 *
 * Baggrundsbilledet står bevidst i style-attributten frem for i CSS-filen,
 * fordi værdien er dynamisk pr. blok. Det er sikkert her, fordi
 * FieldValidator::imagePath() allerede har afvist alt, der ikke er en
 * simpel filsti — ingen anførselstegn, ingen parenteser, ingen '..'.
 */
?>
<section class="block block--hero"<?= eAttr(['style' => $cssVars]) ?>>
    <?php if ($bgImage !== ''): ?>
        <div class="hero__background" style="background-image:url('<?= e($bgImage) ?>')" role="presentation"></div>
    <?php endif; ?>

    <div class="hero__panel">
        <h1 class="hero__title"><?= e($title) ?></h1>

        <?php if ($address !== '' || $phone !== ''): ?>
            <p class="hero__meta">
                <?php if ($address !== ''): ?>
                    <span class="hero__address"><?= e($address) ?></span>
                <?php endif; ?>

                <?php if ($phone !== ''): ?>
                    <a class="hero__phone" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>">
                        <?= e($phone) ?>
                    </a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</section>