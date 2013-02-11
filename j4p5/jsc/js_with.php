<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_with extends js_construct {
    function __construct($expr, $statement) {
        list($this->expr, $this->statement) = func_get_args();
    }
    function emit($w=0) {
        $o = "jsrt::push_scope(js_obj(".$this->expr->emit(1)."));\n";
        $o.= $this->statement->emit(1);
        $o.= "jsrt::pop_scope();\n";
        return $o;
    }
}

?>