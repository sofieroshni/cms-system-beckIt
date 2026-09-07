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

            'imageUrl' => [
                'type' => 'text',
                'label' => 'Billede via link'
            ],

            'imageUrlSecond' => [
                'type' => 'text',
                'label' => 'Billede via link'
            ],
        ];
    }

    public static function render(array $data): string
    {
        $title = htmlspecialchars(
            $data['contentTitle'] ?? 'Indsæt Overskrift'
        );

        $imgUrl = htmlspecialchars(
            $data['imageUrl'] ?? '../assets/images/no-image.jpg'
        );

        $imgUrl2 = htmlspecialchars(
            $data['imageUrlSecond'] ?? '../assets/images/no-image.jpg'
        );

        $whitetext = htmlspecialchars(
            $data['content'] ?? 'Indsæt tekst..'
        );

        return "
            <section>
                <div class='mudbackground'>

                    <div class='column'>
                        <h2>{$title}</h2>
                        <p>{$whitetext}</p>
                    </div>

                    <div class='column'>
                        <img src='{$imgUrl}' alt=''></img>
                        <img src='{$imgUrl2}' alt=''></img>
                    </div>

                </div>
            </section>

            <style>
           
                .mudbackground {
                    background-color: #686666;
                    min-width: 100%!important;
                    display: flex;
                    justify-content: center;
                    flex-direction: row;
                    font-family: 'Jost', sans-serif;
                }

                .column {
                    display: flex;
                    flex-direction: column;
                    width:500px;
                    height:400px
                    padding:20px;
                    gap:20px;
                }
                    .column{
                    width:500px;
                    height:500px;
                    padding:20px;
                    }
                    .column p {
                    font-size:20px;
                    color:#D9D9D9;
                    }
  .column h2 {
                    font-size:45px;
                    color:#D9D9D9;

                    }
            
            </style>
        ";
    }
}