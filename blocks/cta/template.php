<?php
/**
 * Template for CallToAction-blokken.
 *
 * @var string $title
 * @var string $text
 * @var string $buttonLabel
 * @var string $buttonHref
 * @var string $cssVars
 */
?>
<section class="block block--cta"<?= eAttr(['style' => $cssVars]) ?>>
    <div class="cta__inner">
        <?php if ($title !== ''): ?>
            <h2 class="cta__title"><?= e($title) ?></h2>
        <?php endif; ?>

        <?php if ($text !== ''): ?>
            <p class="cta__text"><?= nl2br(e($text)) ?></p>
        <?php endif; ?>

        <?php if ($buttonLabel !== ''): ?>
            <a class="cta__button" href="<?= e($buttonHref) ?>"><?= e($buttonLabel) ?></a>
        <?php endif; ?>
    </div>
</section>