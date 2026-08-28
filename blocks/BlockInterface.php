<?php 
interface BlockInterface{
public static function getSchema(): array;
public static function render(array $data): string; 

}


/// Dette er en "kontrakt" — enhver blok-klasse SKAL have disse to funktioner.
// Det sikrer, at BlockRegistry og PageRenderer altid kan regne med,
// at en blok kan fortælle om sig selv (getSchema) og vise sig selv (render).