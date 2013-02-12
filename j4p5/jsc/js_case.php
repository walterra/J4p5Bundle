<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_case extends js_construct {
    function __construct($expr, $code) {
        list($this->expr, $this->code) = func_get_args();
    }
    function emit($w=0) {
        if (is_int($this->expr) && $this->expr == 0) {
            $o = "  default:\n";
        } else {
            $o = "  case (jss::js_bool(jsrt::expr_strict_equal(\$".$this->e.",".$this->expr->emit(1)."))):\n";
        }
        foreach ($this->code as $code) {
            $o .= "    ".trim(str_replace("\n", "\n    ", $code->emit(1)))."\n";
        }
        return $o;
    }
}

?>