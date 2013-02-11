<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_new extends js_construct {
    function __construct($expr) {
        list($this->expr) = func_get_args();
        #-- if direct child is a js_call object, vampirize it.
        if (get_class($this->expr)=="js_call") {
            $this->args = $this->expr->args;
            $this->expr = $this->expr->expr;
        } else {
            $this->args = array();
        }
    }
    function emit($w=0) {
        $args=array();
        foreach ($this->args as $arg) {
            $args[] = $arg->emit(1);
        }
        return "jsrt::_new(".$this->expr->emit(1).", array(".implode(",",$args) ."))";
    }
}

?>