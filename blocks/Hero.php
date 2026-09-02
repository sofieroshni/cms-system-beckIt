<?php
require_once __DIR__ . '/BlockInterface.php';

class HeroBlock implements BlockInterface {
    // Beskriver hvilke felter admin-editoren skal vise, når man redigerer en Hero-blok
    public static function getSchema(): array {
        return [
            'title'    => ['type' => 'text',  'label' => 'Overskrift'],
            'address'  => ['type' => 'text',  'label' => 'Adresse'],
            'phone'    => ['type' => 'text',  'label' => 'Telefon'],
            'bg_image' => ['type' => 'image', 'label' => 'Baggrundsbillede'],
        ];
    }

    // Genererer den faktiske HTML til den offentlige side
    public static function render(array $data): string {
        $title   = htmlspecialchars($data['title']   ?: 'Bridge-navn');
        $address = htmlspecialchars($data['address'] ?: 'adresse');
        $phone   = htmlspecialchars($data['phone']   ?: 'telefon');
        $bg      = htmlspecialchars($data['bg_image'] ?: 'vælg billede');

        return "
            <section class='hero' style=\"background-image:url('{$bg}')\">
            <div class='hero-title'>      
                  <h1>{$title}</h1>
                    </div>      
                    <div class='hero-info'> 
                    <span>{$address}</span>
                    <span>{$phone}</span>
                    </div>
                   
            </section>
     <style>
    *{
     padding:0px;
     margin:0px;
     overflow-x:hidden;
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
            width:100vw;
            padding: 16px;
            margin:0px;
                
           }
           h1{color:white;
            font-size:40px;
            font-weigt:extra-bold;
font-family: 'Jost', sans-serif;
letter-spacing: 5px;
}

        .hero > .hero-title{
            display:flex;
            align-items:center;
            justify-content:center;
            text-align:center;
            width:458px;
            background-color:#213377;
            margin-bottom:8px;
            }

        .hero > .hero-info{   
            display:flex;
            text-align:center;
            justify-content:space-between;
            width:458px;
            padding:0px;
            text-align:center;


            }

            .hero > .hero-info >span{
            display:flex;
            // background-color:red;
            color:white;
            font-size:16px;
            font-weight:medium;
            width:225px;
            border-radius:3px;
             background-color:#213377;
            text-align:center;
            align-items:center;
            justify-content:center;
    }

        </style>
        ";
 
         
    }
  
}
