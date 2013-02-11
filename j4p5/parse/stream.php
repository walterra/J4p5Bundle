<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

use \Walterra\J4p5Bundle\j4p5\parse\Helpers;
use \Walterra\J4p5Bundle\j4p5\parse\token;
use \Walterra\J4p5Bundle\j4p5\parse\point;

class stream {
    function __construct($string) {
        $this->string = $string;
        $this->col = 0;
        $this->line = 1;
    }
    function consume($str) {
        $len = strlen($str);
        $this->string = substr($this->string, $len);
        $this->col += $len;
    }
    function test($pattern) {
        if ($match = Helpers::preg_pattern_test($pattern,$this->string)) {
            $this->consume($match[0]);
            return $match;
        }
    }
    function default_rule() {
        if (!strlen($this->string)) return Helpers::null_token();

        $start = $this->pos();
        $ch = $this->string[0];
        $this->consume($ch);
        $stop = $this->pos();
        return new token('c'.$ch, $ch, $start, $stop);
    }
    function pos() {
        return new point($this->line, $this->col);
    }
}

