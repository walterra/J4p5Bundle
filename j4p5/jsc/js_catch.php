<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_catch extends js_construct {
    function __construct($id, $code) {
        list($this->id, $this->code) = func_get_args();
    }
    function emit($w=0) {
        // this kind of code makes you wonder why this is even an object. absorb me. please. XXX
        return $this->code->emit(1);
    }
}

?>