<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_binary_op;

class js_strict_not_equal extends js_binary_op {
    function __construct() {parent::__construct(func_get_args(),1,1);}
}

?>