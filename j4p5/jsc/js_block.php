<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_block extends js_construct {
    function __construct($a) {
        $this->statements = $a;
    }
    function emit($w=0) {
        $o = "{\n";
        foreach ($this->statements as $statement) {
            $o.= "  ".trim(str_replace("\n", "\n  ", $statement->emit(1)))."\n";
        }
        $o.= "}\n";
        return $o;
    }
}

?>