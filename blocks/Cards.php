<?php

require_once __DIR__ . '/BlockInterface.php';

class CardsBlock implements BlockInterface
{
    public static function getSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Overskrift'
            ],
            'rows_json' => [
                'type' => 'text',
                'label' => 'Tabel'
            ]
        ];
    }

    public static function render(array $data): string
    {
        $overskrift = htmlspecialchars(
            $data['title'] ?? 'Overskrift',
            ENT_QUOTES,
            'UTF-8'
        );

        $rawRows = $data['rows_json'] ?? [];

        $cardsData = is_string($rawRows)
            ? json_decode($rawRows, true)
            : $rawRows;

        if (!is_array($cardsData)) {
            $cardsData = [];
        }

        $html = '';

        foreach ($cardsData as $card) {
            $Titel = htmlspecialchars(
                $card['titel'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            );

            $Tekst = htmlspecialchars(
                $card['tekst'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            );

            $Tidspunkt = htmlspecialchars(
                $card['tidspunkt'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            );

            $html .= "
                <tr>
                    <td>{$Titel}</td>
                    <td>{$Tekst}</td>
                    <td>{$Tidspunkt}</td>
                </tr>
            ";
        }

        return "
            <section class=\"cards-block\">
                <h2>{$overskrift}</h2>

                <table>
                    <tbody>
                        {$html}
                    </tbody>
                </table>
            </section>
        ";
    }
}