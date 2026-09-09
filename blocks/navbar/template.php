<?php
/**
 * Template for Navbar-blokken.
 *
 * @var string                            $logo     Færdig, valideret URL.
 * @var string                            $logoAlt
 * @var array<int, array<string, string>> $links    Hver med label og href.
 * @var string                            $cssVars
 *
 * Adresserne er allerede regnet ud i NavbarBlock::render(). Templaten
 * kender hverken sitets struktur eller sin egen placering i den.
 */
?>
<nav class="block block--navbar"<?= eAttr(['style' => $cssVars]) ?> aria-label="Hovedmenu">
    <div class="navbar__inner">
        <?php if ($logo !== ''): ?>
            <img class="navbar__logo" src="<?= e($logo) ?>" alt="<?= e($logoAlt) ?>">
        <?php endif; ?>

        <?php if ($links !== []): ?>
            <ul class="navbar__links">
                <?php foreach ($links as $link): ?>
                    <li>
                        <a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</nav>