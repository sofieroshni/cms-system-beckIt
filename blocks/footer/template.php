<?php
/**
 * Template for Footer-blokken.
 *
 * @var string                            $logo
 * @var string                            $logoAlt
 * @var array<int, array<string, string>> $links
 * @var string                            $note
 * @var string                            $cssVars
 *
 * <footer> frem for <div>: rollen staar i selve markup'en.
 */
?>
<footer class="block block--footer"<?= eAttr(['style' => $cssVars]) ?>>
    <div class="footer__inner">
        <?php if ($logo !== ''): ?>
            <img class="footer__logo" src="<?= e($logo) ?>" alt="<?= e($logoAlt) ?>">
        <?php endif; ?>

        <?php if ($links !== []): ?>
            <ul class="footer__links">
                <?php foreach ($links as $link): ?>
                    <li><a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php if ($note !== ''): ?>
        <p class="footer__note"><?= e($note) ?></p>
    <?php endif; ?>
</footer>