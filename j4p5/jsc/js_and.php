<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_binary_op;

class js_and extends js_binary_op {
    function __construct() {parent::__construct(func_get_args(),1,1);}
    #-- using plain functions would prevent short-circuiting
    function emit($w=0) {
        $tmp=jsc::gensym("sc");
        return "(!jss::js_bool(\$$tmp=".$this->arg1->emit(1).")?\$$tmp:".$this->arg2->emit(1).")";
    }
}

?>