<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;

class js_for_in extends js_construct {
    function __construct($one, $list, $statement) {
        list($this->one, $this->list, $this->statement) = func_get_args();
    }
    function emit($w=0) {
        $key=jsc::gensym("fv");
        js_source::$nest++;
        $o ="foreach (".$this->list->emit(1)." as \$$key) {\n";
        if (get_class($this->one)=="js_var") {
            $v = $this->one->emit_for();
        } else {
            $v = $this->one->emit();
        }
        $o.="  jsrt::expr_assign($v, jss::js_str(\$$key));\n";
        $o.= "  ".trim(str_replace("\n", "\n  ", $this->statement->emit(1)))."\n";
        $o.="}";
        js_source::$nest--;
        return $o;
    }
}

?>