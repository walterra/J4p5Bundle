<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_binary_op;
use Walterra\J4p5Bundle\j4p5\jsc;

class js_or extends js_binary_op {
    function __construct() {parent::__construct(func_get_args());}
    #-- using plain functions would prevent short-circuiting
    function emit($w=0) {
        $tmp=jsc::gensym("sc");
        return "(js_bool(\$$tmp=".$this->arg1->emit(1).")?\$$tmp:".$this->arg2->emit(1).")";
    }
}

?>