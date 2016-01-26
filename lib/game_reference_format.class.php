<?php

include_once('game_reference_utils.inc.php');

// Fixes formatting quirks from the game reference format.
class game_reference_format {
    static function string($str) {
        return decode_octal(remove_quotes($str));
    }

    static function number($str) {
        return intval($str, 10);
    }

}
