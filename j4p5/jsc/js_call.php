<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_call extends js_construct {
    function __construct($expr, $args) {
        list($this->expr, $this->args) = func_get_args();
    }
    function emit($w=0) {
        $args=array();
        foreach ($this->args as $arg) {
            $args[] = $arg->emit(1);
        }
        return "jsrt::call(".$this->expr->emit().", array(".implode(",",$args) ."))";
    }
}


?>