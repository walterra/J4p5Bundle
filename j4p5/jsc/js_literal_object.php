<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_literal_object extends js_construct {
    function __construct($o=array()) {
        $this->obj = $o;
    }
    function emit($w=0) {
        $a = array();
        for ($i=0;$i<count($this->obj);$i++) {
            $a[] = $this->obj[$i]->emit();
        }
        return "jsrt::literal_object(".implode(",",$a).")";
    }
}

?>