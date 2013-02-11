<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;

class js_switch extends js_construct {
    function __construct($expr, $block) {
        list($this->expr, $this->block) = func_get_args();
    }
    function emit($w=0) {
        $e = jsc::gensym("jsrt_sw");
        js_source::$nest++;    
        $o  = "\$$e = ".$this->expr->emit(1).";\n";
        $o .= "switch (true) {\n";
        foreach ($this->block as $case) {
            $case->e = $e;
            $o .= $case->emit(1);
        }
        $o.="\n}\n";
        js_source::$nest--;
        return $o;
    }
}

?>