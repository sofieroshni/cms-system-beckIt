<?php 
require_once __DIR__ . '/BlockInterface.php';


    class TabelBlock implements BlockInterface {
        public static function getSchema() : array {
             return[
        
        'contentTitle'=> ['type' => 'text', 'label' => 'Titel'],
        'contentTitle2' =>  ['type' => 'text', 'label' => 'Titel'],

        ];
        }
        public static function render (array $data): string {
            $bjælker = [htmlspecialchars($data['contentTitle']), ($data['contentTitle2'])];
            

              return "
              <section>
        <table>
  <tr>
    <th>Underklub</th>
    <th>Spilledag</th>
    <th>Tidspunkt</th>
  </tr>
  <tr>
  </tr> 
  <style>
  </style>;
  </section>
  ";
        }
        
      
        }
        
    
 