<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_binary_op;

class js_lt extends js_binary_op {
    function __construct() {parent::__construct(func_get_args(),1,1);}
    function emit($w=0) {
        // weak attempt at speeding things. probably not worth it.
        return "jsrt::cmp(".$this->arg1->emit(1).",".$this->arg2->emit(1).", 1)";
    }
}

?>