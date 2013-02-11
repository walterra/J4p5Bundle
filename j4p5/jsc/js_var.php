<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;
use Walterra\J4p5Bundle\j4p5\jsc\js_assign;
use Walterra\J4p5Bundle\j4p5\jsc\js_identifier;

class js_var extends js_construct {
    function __construct($args) {
        $this->vars = $args;
    }
    function emit($w=0) {
        $o = '';
        foreach ($this->vars as $var) {    
            list($id, $init) = $var;
            js_source::$that->addVariable($id);
            if (get_class($init)) {
                $obj = new js_assign(new js_identifier($id), $init);
                $o .= $obj->emit(1);
                $o .= ";\n";
            }
        }
        return $o;
    }
    static public function really_emit($arr) {
        if (count($arr)==0) return '';
        $l = "'".implode("','",array_unique($arr))."'";
        return "jsrt::define_variables($l);\n";
    }
    function emit_for() {
        $this->emit(1);
        return "jsrt::id('".$this->vars[0][0]."')";
    }
}

?>