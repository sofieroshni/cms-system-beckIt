<?php
declare(strict_types=1);

/**
 * Validerer og renser værdier på vej ind i databasen.
 *
 * Det her er systemets sikkerhedsgrænse for blok-data.
 *
 * Hvorfor validering og ikke bare escaping:
 * Den gamle Hero-blok gjorde `htmlspecialchars` på baggrundsbilledet og
 * skrev det derefter ind i style="background-image:url('...')".
 * Det er utilstrækkeligt, fordi browseren først afkoder HTML-entiteter og
 * DEREFTER sender resultatet til CSS-parseren. En escapet apostrof bliver
 * afkodet tilbage til en rigtig apostrof, og så er man ude af url() og kan
 * skrive vilkårlig CSS.
 *
 * Løsningen er, at ulovlige værdier aldrig når databasen. Er en farve først
 * gemt, VED vi at den matcher et hex-mønster. Er en billedsti gemt, VED vi
 * at den ikke indeholder anførselstegn eller '..'.
 *
 * Princip: ugyldigt input kaster ikke fejl — det falder tilbage til feltets
 * standardværdi. En redaktør skal ikke miste hele sit arbejde, fordi ét
 * felt var forkert.
 */
final class FieldValidator
{
    /** Skrifttyper brugeren må vælge imellem. */
    public const ALLOWED_FONTS = ['Jost', 'Roboto', 'Oleo Script', 'Georgia', 'system-ui'];

    /** Billedformater der må refereres. */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

    private function __construct()
    {
    }

    /**
     * Validerer et helt sæt værdier mod et skema.
     *
     * Kun felter, der findes i skemaet, kommer med videre. Det er en
     * allowlist — præcis samme princip som jeres oprindelige
     * save-block.php brugte, nu blot med typetjek oveni.
     *
     * @param array<string, array<string, mixed>> $schema
     * @param array<string, mixed>                $input
     * @return array<string, mixed>
     */
    public static function validateAll(array $schema, array $input): array
    {
        $result = [];

        foreach ($schema as $name => $field) {
            $result[$name] = self::validateField($field, $input[$name] ?? null);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $field Feltdefinitionen fra skemaet.
     */
    public static function validateField(array $field, mixed $value): mixed
    {
        $type    = $field['type'] ?? 'text';
        $default = $field['default'] ?? '';

        return match ($type) {
            'text'     => self::text($value, $default, $field['max'] ?? 255),
            'textarea' => self::text($value, $default, $field['max'] ?? 5000),
            'url'      => self::url($value, $default),
            'image'    => self::imagePath($value, $default),
            'color'    => self::color($value, $default),
            'number'   => self::number($value, $default, $field),
            'select'   => self::select($value, $default, $field['options'] ?? []),
            'repeater' => self::repeater($value, $field),
            default    => $default,
        };
    }

    private static function text(mixed $value, mixed $default, int $max): string
    {
        if (!is_string($value)) {
            return (string) $default;
        }

        // Kontroltegn fjernes; de har ingen legitim plads i redaktionelt
        // indhold og kan skjule ondsindet input i logs og editorer.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return mb_substr(trim($value), 0, $max, 'UTF-8');
    }

    /**
     * Tillader kun http, https, mailto, tel og '#'.
     *
     * Uden denne kontrol kunne en redaktør — bevidst eller ved
     * copy-paste — gemme et javascript:-link, som ville køre kode hos
     * enhver besøgende på den udgivne side.
     */
    private static function url(mixed $value, mixed $default): string
    {
        if (!is_string($value)) {
            return (string) $default;
        }

        $value = trim($value);

        // Skabelonernes døde dummy-links.
        if ($value === '' || $value === '#') {
            return $value;
        }

        // Relative links inden for sitet.
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            return $value;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)
            ? $value
            : (string) $default;
    }

    /**
     * En billedsti skal pege på en fil inde i projektet.
     *
     * Afviser absolutte stier, URL'er, '..'-sekvenser og alt der ikke har
     * en kendt billedendelse. Det er dét, der gør stien sikker at skrive
     * ind i CSS bagefter.
     */
    private static function imagePath(mixed $value, mixed $default): string
    {
        if (!is_string($value) || trim($value) === '') {
            return (string) $default;
        }

        $value = ltrim(trim($value), '/');

        if (str_contains($value, '..') || str_contains($value, '://')) {
            return (string) $default;
        }

        // Kun bogstaver, tal, bindestreg, understreg, punktum og skråstreg.
        // Anførselstegn og parenteser — som CSS-injection kræver — er
        // dermed udelukket.
        if (preg_match('#^[A-Za-z0-9_\-./]+$#', $value) !== 1) {
            return (string) $default;
        }

        $extension = strtolower((string) pathinfo($value, PATHINFO_EXTENSION));

        return in_array($extension, self::IMAGE_EXTENSIONS, true)
            ? $value
            : (string) $default;
    }

    /**
     * Kun 3- eller 6-cifret hex. Det er dét, farvevælgeren i browseren
     * leverer, og det kan ikke bruges til at bryde ud af en CSS-værdi.
     */
    private static function color(mixed $value, mixed $default): string
    {
        if (!is_string($value)) {
            return (string) $default;
        }

        $value = trim($value);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1
            ? strtolower($value)
            : (string) $default;
    }

    /**
     * @param array<string, mixed> $field
     */
    private static function number(mixed $value, mixed $default, array $field): int
    {
        if (!is_numeric($value)) {
            return (int) $default;
        }

        $min = (int) ($field['min'] ?? 0);
        $max = (int) ($field['max'] ?? 9999);

        // Klemmes ind i intervallet frem for at afvises, så en
        // skriftstørrelse på 5000 bare bliver til det tilladte maksimum.
        return max($min, min($max, (int) $value));
    }

    /**
     * @param array<int, string> $options
     */
    private static function select(mixed $value, mixed $default, array $options): string
    {
        return (is_string($value) && in_array($value, $options, true))
            ? $value
            : (string) $default;
    }

    /**
     * Gentagne rækker — fx de tre kort i Service-sektionen eller
     * navbarens links. Hver række valideres mod underskemaet.
     *
     * @param array<string, mixed> $field
     * @return array<int, array<string, mixed>>
     */
    private static function repeater(mixed $value, array $field): array
    {
        if (!is_array($value)) {
            return $field['default'] ?? [];
        }

        $subSchema = $field['fields'] ?? [];
        $maxRows   = (int) ($field['max_rows'] ?? 50);
        $rows      = [];

        foreach ($value as $row) {
            if (count($rows) >= $maxRows) {
                break;
            }
            if (is_array($row)) {
                $rows[] = self::validateAll($subSchema, $row);
            }
        }

        return $rows;
    }
}