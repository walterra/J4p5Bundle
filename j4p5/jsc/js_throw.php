<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_throw extends js_construct {
    /* js exceptions are sufficiently different from php5 exceptions to make them un-leverage-able. */
    function __construct($expr) {
        $this->expr = $expr;
    }
    function emit($w=0) {
        //return "return new js_completion(".$this->expr->emit().");\n";
        return "throw new js_exception(".$this->expr->emit(1).");\n";
    }
}

?>