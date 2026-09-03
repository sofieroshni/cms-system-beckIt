<?php
require_once __DIR__ . '/BlockInterface.php';

class HeroBlock implements BlockInterface {

    // Felter som admin-editoren skal vise
    public static function getSchema(): array {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Overskrift'
            ],

            'address' => [
                'type' => 'text',
                'label' => 'Adresse'
            ],

            'phone' => [
                'type' => 'text',
                'label' => 'Telefon'
            ],

            'bg_image' => [
                'type' => 'image',
                'label' => 'Baggrundsbillede',
                'default' => 'assets/images/no-image.jpg'
            ],

            'primary_color' => [
                'type' => 'color',
                'label' => 'Primær farve',
                'default' => '#213377'
            ],

            'text_color' => [
                'type' => 'color',
                'label' => 'Tekstfarve',
                'default' => '#ffffff'
            ]
        ];
    }


    // Genererer HTML til den offentlige side
    public static function render(array $data): string {

        // Content defaults
        $title   = htmlspecialchars($data['title'] ?? 'Bridge-navn');
        $address = htmlspecialchars($data['address'] ?? 'adresse');
        $phone   = htmlspecialchars($data['phone'] ?? 'telefon');
        $bg      = htmlspecialchars($data['bg_image'] ?? '/assets/images/no-image.jpg');

        // Styling defaults
        $primaryColor = htmlspecialchars(
            $data['primary_color'] ?? '#213377'
        );

        $textColor = htmlspecialchars(
            $data['text_color'] ?? '#ffffff'
        );


        return "
            <section
                class='hero'
                style=\"background-image: url('{$bg}')\"
            >

                <div
                    class='hero-title'
                    style=\"background-color: {$primaryColor}\"
                >
                    <h1 style=\"color: {$textColor}\">
                        {$title}
                    </h1>
                </div>

                <div class='hero-info'>

                    <span
                        style=\"
                            background-color: {$primaryColor};
                            color: {$textColor};
                        \"
                    >
                        {$address}
                    </span>

                    <span
                        style=\"
                            background-color: {$primaryColor};
                            color: {$textColor};
                        \"
                    >
                        {$phone}
                    </span>

                </div>

            </section>


            <style>

                * {
                    padding: 0;
                    margin: 0;
                    overflow-x: hidden;
                }

                .hero {
                    font-family: 'Jost', sans-serif;

                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;

                    background-size: cover;
                    background-position: center;

                    height: 338px;
                    width: 100vw;

                    padding: 16px;
                    margin: 0;
                }

                .hero h1 {
                    font-size: 40px;
                    font-weight: 800;
                    font-family: 'Jost', sans-serif;
                    letter-spacing: 5px;
                }

                .hero > .hero-title {
                    display: flex;
                    align-items: center;
                    justify-content: center;

                    text-align: center;

                    width: 458px;

                    margin-bottom: 8px;
                }

                .hero > .hero-info {
                    display: flex;

                    text-align: center;
                    justify-content: space-between;

                    width: 458px;
                    padding: 0;
                }

                .hero > .hero-info > span {
                    display: flex;

                    font-size: 16px;
                    font-weight: 500;

                    width: 225px;

                    border-radius: 3px;

                    text-align: center;
                    align-items: center;
                    justify-content: center;
                }

            </style>
        ";
    }
}