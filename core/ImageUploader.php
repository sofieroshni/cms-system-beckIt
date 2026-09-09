<?php
declare(strict_types=1);

/**
 * Modtager og gemmer uploadede billeder.
 *
 * Klassen er systemets mest sikkerhedsfølsomme kode. Et upload-endpoint,
 * der ikke kontrollerer hvad det modtager, er den klassiske vej til at få
 * kørbar kode ind på en server.
 *
 * Fire regler bærer det:
 *
 * 1. TYPEN AFGØRES AF INDHOLDET, IKKE AF NAVNET.
 *    Filendelsen er brugerinput og siger ingenting. getimagesize() åbner
 *    filen og afgør, hvad den faktisk er. En .jpg der i virkeligheden er
 *    PHP-kode, falder på det trin.
 *
 * 2. FILNAVNET GENERERES.
 *    Det oprindelige navn bruges aldrig. Uploader nogen '../../index.php',
 *    når det navn aldrig filsystemet. Vi laver et nyt ud fra tilfældige
 *    bytes plus den endelse, vi selv har udledt af indholdet.
 *
 * 3. SVG AFVISES.
 *    En SVG er en XML-fil, der kan indeholde JavaScript. Den er reelt et
 *    HTML-dokument forklædt som billede, og den hører ikke hjemme i et
 *    upload-felt, som en redaktør bruger.
 *
 * 4. STØRRELSEN HAR ET LOFT.
 *    Uden det kan et par uploads fylde disken.
 */
final class ImageUploader
{
    /** 8 MB. Rigeligt til et fotografi, lavt nok til at begrænse skade. */
    private const MAX_BYTES = 8 * 1024 * 1024;

    /**
     * Billedtyper vi accepterer, og den endelse hver af dem får.
     *
     * Nøglerne er konstanter fra getimagesize(), altså udledt af filens
     * indhold. SVG optræder bevidst ikke: getimagesize() genkender den
     * ikke som billede, og den skal heller ikke igennem.
     *
     * @var array<int, string>
     */
    private const ALLOWED = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    public function __construct(private readonly string $uploadDir)
    {
    }

    /**
     * Gemmer en uploadet fil og returnerer dens sti relativt til
     * projektroden, fx 'uploads/2026/09/a1b2c3d4e5f6.jpg'.
     *
     * @param array<string, mixed> $file En post fra $_FILES.
     *
     * @throws RuntimeException med en besked, brugeren kan forstå.
     */
    public function store(array $file): string
    {
        $this->assertUploadSucceeded($file);

        $temporaryPath = (string) ($file['tmp_name'] ?? '');

        // Bekræfter at filen rent faktisk kom fra en HTTP-upload og ikke
        // er en sti, nogen har fået serveren til at pege på.
        if (!is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('Ugyldig upload.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('Billedet er for stort. Maksimum er 8 MB.');
        }

        $extension = $this->detectExtension($temporaryPath);

        // Månedsmapper holder antallet af filer pr. mappe nede, så
        // filhåndteringen stadig er til at arbejde med efter et par år.
        $subDirectory = date('Y/m');
        $directory    = $this->uploadDir . '/' . $subDirectory;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Kunne ikke oprette mappen til billeder.');
        }

        // Tilfældigt navn. Ingen del af brugerens filnavn overlever.
        $filename = bin2hex(random_bytes(8)) . '.' . $extension;
        $target   = $directory . '/' . $filename;

        if (!move_uploaded_file($temporaryPath, $target)) {
            throw new RuntimeException('Billedet kunne ikke gemmes.');
        }

        // Ikke kørbar. Betyder intet på Windows, men filerne skal kunne
        // flyttes til en Linux-server uden at blive et problem.
        chmod($target, 0644);

        return 'uploads/' . $subDirectory . '/' . $filename;
    }

    /**
     * @param array<string, mixed> $file
     */
    private function assertUploadSucceeded(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_OK) {
            return;
        }

        // PHP's egne fejlkoder oversættes til noget, en redaktør kan
        // handle på. 'UPLOAD_ERR_INI_SIZE' siger ingen bruger noget.
        throw new RuntimeException(match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE
                => 'Billedet er for stort.',
            UPLOAD_ERR_PARTIAL
                => 'Overførslen blev afbrudt. Prøv igen.',
            UPLOAD_ERR_NO_FILE
                => 'Der blev ikke valgt nogen fil.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE
                => 'Serveren kunne ikke gemme filen.',
            default
                => 'Upload mislykkedes.',
        });
    }

    /**
     * Afgør filtypen ud fra indholdet og returnerer den endelse, filen
     * skal have.
     */
    private function detectExtension(string $path): string
    {
        // Returnerer false for alt, der ikke er et billede, den kender —
        // herunder PHP-filer, tekstfiler og SVG.
        $info = @getimagesize($path);

        if ($info === false || !isset(self::ALLOWED[$info[2]])) {
            throw new RuntimeException(
                'Filen er ikke et gyldigt billede. Brug JPG, PNG, GIF eller WebP.'
            );
        }

        return self::ALLOWED[$info[2]];
    }
}