<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_if extends js_construct {
    function __construct($cond, $ifblock, $elseblock=null) {
        $this->cond = $cond;
        $this->ifblock = $ifblock;
        $this->elseblock = $elseblock;
    }
    function emit($w=0) {
        $o = "if (jss::js_bool(".$this->cond->emit(1).")) ".$this->ifblock->emit(1);
        if ($this->elseblock) {
            $o = rtrim($o) . " else ".$this->elseblock->emit(1)."\n";
        }
        return $o;
    }
}

?>