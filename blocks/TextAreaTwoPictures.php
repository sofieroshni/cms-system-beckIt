<?php
require_once __DIR__ . '/BlockInterface.php';

class TextAreaTwoPicturesBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'contentTitle'=> ['type' => 'text', 'label' => 'Titel'],
            'content' => ['type' => 'richtext', 'label' => 'Tekst'],
            'TextsArea' => ['type'=> 'richtext' , 'label' => 'Tekst grå'],
            'TextArea3' => ['type' => 'text', 'label' => 'Titel grå']
        ];
    }

    public static function render(array $data): string {        
        $title = htmlspecialchars($data['contentTitle'] ?: 'indsæt under-overskrift');
        $grayText = htmlspecialchars($data['content'] ?: 'indsæt tekst');
        $textArea = htmlspecialchars($data['TextsArea'] ?? 'tekst felt tekst felt tekst felt tekst felt tekst felt tekst felt tekst felt tekst felt tekst felt 
tekst felt tekst felt tekst felt tekst felt tekst felt tekst felt tekst felt 
tekst felt tekst felt tekst felt tekst felt tekst felt ');
        $textArea3 = htmlspecialchars($data['TextArea3'] ?? 'H3' );
        return "
        <section>
        
        </section>


         <style>
    section{
    background-color:#686666;
    width:100%;
    height:400px;
    display:flex;


    font-family: 'Jost', sans-serif;

    }
    
</style>";
    }
  
}