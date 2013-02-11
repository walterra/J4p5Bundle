<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;

class js_continue extends js_construct {
    function __construct($label) {
        $this->label = $label;
    }
    function emit($w=0) {
        if (js_source::$nest==0) {
            return "ERROR: continue outside of a loop\n*************************\n\n";
        }
        if ($this->label !== ';') {
            $depth = js_source::$nest - js_source::$labels[$this->label];
            $o = "continue $depth;\n";
        } else {
            $o = "continue;\n";
        }
        return $o;
    }
}

?>