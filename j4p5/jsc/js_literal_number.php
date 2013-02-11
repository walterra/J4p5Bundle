<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_literal_number extends js_construct {
    function __construct($v) {
        $this->v = $v;
    }
    function emit($w=0) {
        return "js_int(".$this->v.")";
    }
}

?>