<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\js;

class js_print extends js_construct {
    function __construct() {
        $this->args = func_get_args();
    }

    function emit($w=0) {
        $o='jsrt::write( ';
        $first=true;
        foreach ($this->args as $arg) {
            if ($first) {$first^=true;} else {$o.=",";}
            $o.="(".(js:get_classname_without_namespace($arg)?$arg->emit(1):$arg).")";
        }
        $o.= ");\n";
        return $o;
    }
}

