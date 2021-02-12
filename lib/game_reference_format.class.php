<?php

include_once('game_reference_utils.inc.php');

// Fixes formatting quirks from the game reference format.
class game_reference_format {
    static function string($str) {
        $str = decode_octal(remove_quotes($str));
        return mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
    }

    static function number($str) {
        return intval($str, 10);
    }

}
