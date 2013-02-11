<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_nop extends js_construct {
    function emit($w=0) {
        return '';
    }
}

?>