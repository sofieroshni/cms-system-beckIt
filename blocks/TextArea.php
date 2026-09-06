<?php
require_once __DIR__ . '/BlockInterface.php';

class TextAreaBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'contentTitle'=> ['type' => 'text', 'label' => 'Titel'],
            'content' => ['type' => 'richtext', 'label' => 'Tekst'],
        ];
    }

    public static function render(array $data): string {        
        $title = htmlspecialchars($data['contentTitle'] ?: 'indsæt under-overskrift');
        $grayText = htmlspecialchars($data['content'] ?: 'indsæt tekst');
        return "
        <section>
        <div class='textarea-block'>
        <h2 class='h2'>{$title}</h2>
        <p class='graytext'>______________</p>
        <p class='graytext'>{$grayText}</p>
        </div>
        </section>


         <style>
        section{
        font-family:'Jost' sans-serif;
        width: 100%;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;

        }
         .textarea-block{
         width: 997px;
         height: 823px;
         background-color:#213377;
         
         display: flex;
         flex-direction:column;
         align-items:center;
         justify-content:center;
         text-align:center;



         
         }
        .graytext{
        color:#D9D9D9;
        margin-top:12px;
        margin-bottom:12px;
    }
.h2{
color:white;
font-family: 'Jost' sans-serif;
margin-bottom:20px;

    }
         </style>";
    }
  
}