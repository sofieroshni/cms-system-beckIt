<?php
require_once __DIR__ . '/BlockInterface.php';

class TextAreaBlueBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'contentTitle'=> ['type' => 'text', 'label' => 'Titel'],
            'content' => ['type' => 'richtext', 'label' => 'Tekst'],
            'TextsArea' => ['type'=> 'richtext' , 'label' => 'Tekst grå'],
            'TextArea3' => ['type' => 'text', 'label' => 'Titel grå']
        ];
    }

    public static function render(array $data): string {        
        $title = htmlspecialchars($data['contentTitle'] ?: 'Indsæt overskrift..');
        $grayText = htmlspecialchars($data['content'] ?: 'Indsæt tekst..');
        $textArea = htmlspecialchars($data['TextsArea'] ?: 'Indsæt en længerer tekst..');
        $textArea3 = htmlspecialchars($data['TextArea3'] ?: 'Indsæt tekst..' );
        return "
        <section>
        <div class='textarea-block'>
        <h2 class='h2'>{$title}</h2>
        <p class='graytext'>______________</p>
        <p class='graytext'>{$grayText}</p>
                     <div class='graydiv'>
                            <h3 class= 'textarea3'> {$textArea3}</h3>
                            <p class='p'> {$textArea} </p>
                        </div>
        </div>
       
        </section>


         <style>
    section{
    margin-top:50px;
    font-family:'Jost' sans-serif;
    width: 100%;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    }
    
    .textarea-block{
        font-family:'Jost', sans-serif;

        width: 750px;
        height: 700px;
        background-color:#213377; display: flex; 
        flex-direction:column;
        align-items:center; 
        justify-content:center;
        text-align:center;  
         }

.graytext{
 color:#D9D9D9;
margin-bottom:12px;

}

.h2{
color:white;
font-family: 'Jost' sans-serif;
margin-top:12px;
}
.graydiv{
width: 680px;
height:468px;
text-align:start;
background-color:#DADDE6;
position:relative;
margin-top:12px;


}
.graydiv::after{
content: '';
width: 18px;
height:468px;
background-color:#DADDE6;
// position:absolute;
// left:-10px;
// top:0;
// opacity:100;
// z-index:10;
}
.textarea3{
font-size:20px;
font-weight: lighter;
color: #1B1818;
padding:18px;
padding-bottom:0px;
}

.p {
color: #1B1818;
margin-top:8px;
font-size:16px;
padding:18px;
padding-top:0px;

}
</style>";
    }
  
}