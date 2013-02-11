<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_identifier extends js_construct {
    function __construct($id) {
        $this->id = $id;
    }
    function emit($wantvalue=0) {
        $v = $wantvalue?"v":"";
        return "jsrt::id$v('".$this->id."')";
    }
}

?>