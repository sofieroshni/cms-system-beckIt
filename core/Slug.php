<?php
declare(strict_types=1);

/**
 * En slug bliver til et mappenavn, når siden eksporteres til statiske
 * filer. Derfor er reglerne strenge: kun små bogstaver, tal og bindestreg.
 *
 * Uden det ender I med filnavne som "Om os & kontakt.html" på en
 * Linux-server, hvor mellemrum, æøå og specialtegn brækker links.
 *
 * MariaDB 10.4 understøtter ikke REGEXP i CHECK-constraints, så reglen
 * håndhæves her i stedet.
 */
final class Slug
{
    private const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Reserveret, fordi navnet kolliderer med eksportens filstruktur. */
    private const RESERVED = ['index', 'assets', 'uploads'];

    private function __construct()
    {
    }

    public static function isValid(string $slug): bool
    {
        if ($slug === '' || strlen($slug) > 255) {
            return false;
        }

        if (in_array($slug, self::RESERVED, true)) {
            return false;
        }

        return preg_match(self::PATTERN, $slug) === 1;
    }

    /**
     * Laver en gyldig slug ud fra en fritekst-titel.
     * "Om os & kontakt" bliver til "om-os-kontakt".
     */
    public static function fromTitle(string $title): string
    {
        $slug = mb_strtolower(trim($title), 'UTF-8');

        // Danske tegn oversættes, før alt andet fjernes — ellers ville
        // "Bestyrelse på Åvej" blive til "bestyrelse-p-vej".
        $slug = strtr($slug, [
            'æ' => 'ae', 'ø' => 'oe', 'å' => 'aa',
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'é' => 'e',  'è' => 'e',  'ê' => 'e',  'á' => 'a',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug === '' ? 'side' : substr($slug, 0, 255);
    }
}