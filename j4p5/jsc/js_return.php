<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc\js_function_definition;
use Walterra\J4p5Bundle\j4p5\jsc\js_exception;
use Walterra\J4p5Bundle\j4p5\jsc\js_syntaxerror;

class js_return extends js_construct {
    function __construct($expr) {
        $this->expr = $expr;
    }
    function emit($w=0) {
        if (js_function_definition::$in_function==0) {
            throw new js_exception(new js_syntaxerror("invalid return"));
        }
        if ($this->expr == ';') {
            return 'return jsrt::$undefined;\n';
        } else {
            return "return ".$this->expr->emit(1).";\n";
        }
    }
}

?>