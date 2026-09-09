<?php
/**
 * Template for Gallery-blokken.
 *
 * @var string                            $title
 * @var array<int, array<string, string>> $images  Hver med src, alt, caption.
 * @var string                            $cssVars
 *
 * Billederne ligger i en <ul>, fordi et galleri er en liste. Det giver
 * skaermlaesere antallet af billeder paa forhaand.
 */
?>
<section class="block block--gallery"<?= eAttr(['style' => $cssVars]) ?>>
    <div class="gallery__inner">
        <?php if ($title !== ''): ?>
            <h2 class="gallery__title"><?= e($title) ?></h2>
        <?php endif; ?>

        <?php if ($images !== []): ?>
            <ul class="gallery__grid">
                <?php foreach ($images as $image): ?>
                    <li class="gallery__item">
                        <figure class="gallery__figure">
                            <img class="gallery__img"
                                 src="<?= e($image['src']) ?>"
                                 alt="<?= e($image['alt']) ?>"
                                 loading="lazy">

                            <?php if ($image['caption'] !== ''): ?>
                                <figcaption class="gallery__caption">
                                    <?= e($image['caption']) ?>
                                </figcaption>
                            <?php endif; ?>
                        </figure>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="gallery__empty">Ingen billeder tilfoejet endnu.</p>
        <?php endif; ?>
    </div>
</section>