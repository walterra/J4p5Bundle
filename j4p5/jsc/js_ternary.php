<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\js;

class js_ternary extends js_construct {
    function __construct() {
        $this->args = func_get_args();
        $this->jsrt_op = substr(js::get_classname_without_namespace($this), 3);
    }
    function emit($w=0) {
        #-- can't use a helper function to maintain the short-circuit thing.
        return "(js_bool(".$this->args[0]->emit(1).")?(".$this->args[1]->emit(1)."):(".$this->args[2]->emit(1)."))";
    }
}

?>