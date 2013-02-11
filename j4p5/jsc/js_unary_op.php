<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

abstract class js_unary_op extends js_construct {
    function __construct($a,$w=0) {
        $this->arg = $a[0];
        $this->wantValue = $w;
        $this->jsrt_op = substr(get_class($this), 3);
    }
    function emit($w=0) {
        return "jsrt::expr_".$this->jsrt_op."(".$this->arg->emit($this->wantValue).")";
    }
}

?>