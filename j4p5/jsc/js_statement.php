<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_statement extends js_construct {
    function __construct($child) {
        $this->child = $child;
    }
    function emit($w=0) {
        return $this->child->emit(1).";\n";
    }
}

?>