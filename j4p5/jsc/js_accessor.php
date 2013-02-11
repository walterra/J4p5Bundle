<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_accessor extends js_construct {
    function __construct($obj, $member, $resolve) {
        list($this->obj, $this->member, $this->resolve) = func_get_args();
    }
    function emit($wantvalue=0) {
        $v = $wantvalue?"v":"";
        return "jsrt::dot$v(".$this->obj->emit(1).",".$this->member->emit($this->resolve).")";
    }
}

?>