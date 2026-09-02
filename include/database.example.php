<?php
declare(strict_types=1);

/**
 * Kopiér denne fil til include/database.php og udfyld værdierne.
 *
 * include/database.php må ALDRIG committes — den står i .gitignore.
 *
 * Filen opretter ikke længere en forbindelse. Den returnerer kun
 * konfiguration; selve forbindelsen bygges af core/Database.php.
 */

return [
    'host'     => 'localhost',
    'database' => 'cms-becktIt',
    'username' => 'cms-becktIt',

    // Standard i XAMPP er brugeren 'root' med tom adgangskode.
    'password' => 'password',
];