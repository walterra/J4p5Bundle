<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;

class js_compound_assign extends js_construct {
    function __construct() {
        list($this->a, $this->b, $this->op) = func_get_args();
    }
    function emit($w=0) {
        switch($this->op) {
            case '*=': $s="expr_multiply"; break;
            case '/=': $s="expr_divide"; break;
            case '%=': $s="expr_modulo"; break;
            case '+=': $s="expr_plus"; break;
            case '-=':  $s="expr_minus"; break;
            case '<<=': $s="expr_lsh"; break;
            case '>>=': $s="expr_rsh"; break;
            case '>>>=': $s="expr_ursh"; break;
            case '&=': $s="expr_bit_and"; break;
            case '^=': $s="expr_bit_xor"; break;
            case '|=': $s="expr_bit_or"; break;
        }
        return "jsrt::expr_assign(".$this->a->emit().",".$this->b->emit(1).",'".$s."')";
    }
}

?>