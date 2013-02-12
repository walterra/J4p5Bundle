<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_do extends js_construct {
    function __construct($expr, $statement) {
        $this->expr = $expr;
        $this->statement = $statement;
    }
    function emit($w=0) {
        js_source::$nest++;
        $o = "do ".rtrim($this->statement->emit(1))." while (jss::js_bool(".$this->expr->emit(1)."));\n";
        js_source::$nest--;
        return $o;
    }
}

?>