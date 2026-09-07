<?php

require_once __DIR__ . '/BlockInterface.php';

class TextAreaTwoPicturesBlock implements BlockInterface
{
    public static function getSchema(): array
    {
        return [
            'contentTitle' => [
                'type' => 'text',
                'label' => 'Titel'
            ],
            'content' => [
                'type' => 'richtext',
                'label' => 'Tekst'
            ],
        ];
    }

    public static function render(array $data): string
    {
        $title = htmlspecialchars(
            $data['contentTitle'] ?? 'Indsæt under-overskrift'
        );

        $whitetext = htmlspecialchars(
            $data['content'] ?: 'Indsæt tekst, Indsæt tekst,Indsæt tekst,Indsæt tekst,Indsæt tekst,Indsæt tekst'
        );

        return "
            <section>
                <div class='mudbackground'>
                    <div class='column'>
                    {$title}
                    {$whitetext}
                    </div>
                     <div class='column'></div>

                </div>
            </section>

            <style>
                .mudbackground {
                    background-color: #686666;
                    width: 100%;
                    display: flex;
                    justify-content:center;
                    flex-direction:row;
                    font-family: 'Jost', sans-serif;
                }
                    .column{
                    background-color:red;
                    width:50%;
                    display: flex;
                    flex-direction: column;

                    }
            </style>
        ";
    }
}