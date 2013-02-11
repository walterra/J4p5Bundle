<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_literal_array extends js_construct {
    function __construct($arr) {
        $this->arr = $arr;
    }
    function emit($w=0) {
        $a = array();
        for ($i=0;$i<count($this->arr);$i++) {
            if ($this->arr[$i]!=NULL) {
                $a[$i] = $this->arr[$i]->emit(1);
            }
        }
        if (count($this->arr)==1 and get_class($this->arr[0])=="js_literal_null") {
            $a = array();
        }

        return "jsrt::literal_array(".implode(",",$a).")";
    }
}

?>