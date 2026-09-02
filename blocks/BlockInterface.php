<?php
declare(strict_types=1);

/**
 * Kontrakten enhver blok skal opfylde.
 *
 * En blok skal kunne fortælle om sig selv (skemaerne) og vise sig selv
 * (render). Fordi kontrakten er fast, kan editoren bygge sin brugerflade
 * automatisk for en hvilken som helst blok, og PageRenderer kan tegne den
 * uden at kende dens indhold.
 *
 * Ændret fra den oprindelige version: skemaet er delt i to.
 * getSchema() beskriver INDHOLD, getStyleSchema() beskriver UDSEENDE.
 * De valideres forskelligt — indhold escapes ved udskrivning, styling
 * tjekkes mod faste værdilister og intervaller — og derfor skal de holdes
 * adskilt hele vejen fra editoren til databasen.
 */
interface BlockInterface
{
    /** Nøglen i databasens block_type-kolonne, fx 'hero'. */
    public static function type(): string;

    /** Navnet brugeren ser i editoren, fx 'Hero'. */
    public static function label(): string;

    /**
     * Indholdsfelter: overskrifter, tekst, billeder, links.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getSchema(): array;

    /**
     * Stylingfelter: skriftstørrelse, skrifttype, farver.
     * Returnér et tomt array, hvis blokken ikke kan styles.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getStyleSchema(): array;

    /**
     * Tegner blokken som HTML.
     *
     * @param array<string, mixed> $settings Validerede indholdsværdier.
     * @param array<string, mixed> $styles   Validerede stylingværdier.
     */
    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string;
}