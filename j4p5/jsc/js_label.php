<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;

class js_label extends js_construct {
    function __construct($label, $block) {
        list($this->label, $this->block) = func_get_args();
        $p = explode(':',$this->label);
        $this->label = $p[0];
    }
    function emit($w=0) {
        // associate this label with current $nest;
        js_source::$labels[$this->label] = js_source::$nest;

        //return "/* ".$this->label." */ ".$this->block->emit();
        return $this->block->emit(1);
    }
}

?>