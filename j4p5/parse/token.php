<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

class token {
    function __construct($type, $text, $start, $stop) {
        $this->type = $type;
        $this->text = $text;
        $this->start = $start;
        $this->stop = $stop;
    }
}

?>
