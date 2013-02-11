<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_this extends js_construct {
    function emit($w=0) {
        return "jsrt::this()"; // should this be a jsrt::$this instead?
    }
}

?>