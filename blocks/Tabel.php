<?php 
require_once __DIR__ . '/BlockInterface.php';


    class TabelBlock implements BlockInterface {
        public static function getSchema() : array {
             return[
        
        'contentTitle'=> ['type' => 'text', 'label' => 'Titel'],

        ];
        }
        public static function render (array $data): string {
            $contentTitle = htmlspecialchars($data['contentTitle'] ?? 'hej');
        }
       
    }
 