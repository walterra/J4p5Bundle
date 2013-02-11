<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_unary_op;

class js_u_plus extends js_unary_op {
    function __construct() {parent::__construct(func_get_args(),1);}
}

?>