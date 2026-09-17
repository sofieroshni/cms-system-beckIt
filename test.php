<?php
require_once __DIR__ . '/bootstrap.php';
var_dump(is_file(APP_ROOT . '/blocks/textsection/TextSection.php'));
var_dump(class_exists('TextSectionBlock'));
var_dump(BlockRegistry::all());