<?php 
require_once __DIR__ . '/BlockInterface.php';

class TabelBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'overskrift'  => ['type' => 'text', 'label' => 'overskrift(valgfrit)'],
            'underklub'  => ['type' => 'text', 'label' => 'Underklub'],
            'spilledag' => ['type' => 'text', 'label' => 'Spilledag'],
            'tidspunkt' => ['type' => 'text', 'label' => 'Tidspunkt'],
        ];
    }

    public static function render(array $data): string {
        // Hent værdierne og rens dem for sikkerhed (XSS)
        $overskrift = htmlspecialchars($data['underklub'] ?? '', ENT_QUOTES, 'UTF-8');
        $underklub = htmlspecialchars($data['underklub'] ?? '', ENT_QUOTES, 'UTF-8');
        $spilledag = htmlspecialchars($data['spilledag'] ?? '', ENT_QUOTES, 'UTF-8');
        $tidspunkt = htmlspecialchars($data['tidspunkt'] ?? '', ENT_QUOTES, 'UTF-8');

        return "
        <section>
            <table>
                <tr>
                    <th>Underklub</th>
                    <th>Spilledag</th>
                    <th>Tidspunkt</th>
                </tr>
                <tr>
                    <td>{$underklub}</td>
                    <td>{$spilledag}</td>
                    <td>{$tidspunkt}</td>
                </tr> 
            </table>
            <style>
                /* Tilføj din CSS her */
            </style>
        </section>
        ";
    }
}