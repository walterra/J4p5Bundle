<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;

class js_for extends js_construct {
    function __construct($init, $cond, $incr, $statement) {
        list($this->init, $this->cond, $this->incr, $this->statement) = func_get_args();
    }
    function emit($w=0) {
        $o = $this->init?$this->init->emit(1):'';
        js_source::$nest++;
        $o.= "for (;".($this->cond?"js_bool(".$this->cond->emit(1).")":'');
        $o.= ";".($this->incr?$this->incr->emit(1):'').") {\n";
        $o.=$this->statement->emit(1);
        $o.="\n}\n";
        js_source::$nest--;
        return $o;
    }
}

?>