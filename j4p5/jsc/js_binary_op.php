<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\js;

abstract class js_binary_op extends js_construct {
    function __construct($a,$w1=0,$w2=0) {
        $this->arg1 = $a[0];
        $this->arg2 = $a[1];
        $this->wantValue1 = $w1;
        $this->wantValue2 = $w2;
        $this->jsrt_op = substr(js::get_classname_without_namespace($this), 3);
    }
    function emit($w=0) {
        return "jsrt::expr_".$this->jsrt_op."(".$this->arg1->emit($this->wantValue1).",".$this->arg2->emit($this->wantValue2).")";
    }
}
