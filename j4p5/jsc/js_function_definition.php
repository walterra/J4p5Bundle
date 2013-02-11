<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;

class js_function_definition extends js_construct {
    static $in_function = 0;
    function __construct($a) {
        list($this->id, $this->params, $this->body) = $a;
        $this->phpid = jsc::gensym("jsrt_uf");
    }
    function toplevel_emit() {
        $o  = "static public function ".$this->phpid."() {\n";
        $o .= "  ".trim(str_replace("\n", "\n  ", $this->body));
        $o .= "\n}\n";
        return $o;  
    }
    function function_emit() {
        self::$in_function++;
        $this->body = $this->body->emit(1); // do it early, to catch inner functions
        self::$in_function--;
        js_source::addFunctionDefinition($this);
        $id= "";
        if (true or $this->id!='') {
            $id = ",'".$this->id."'";
        }
        $p = "";
        if (count($this->params)>0) {
            $p = ",array('".implode("','",$this->params)."')";
        }
        return "jsrt::define_function('".$this->phpid."'".$id.$p.");\n";
    }
    function emit($w=0) {
        #-- if this gets called, we're a function inside an expression.
        js_source::addFunctionExpression($this);
        #-- XXX output something that will return a handle to the function.
        return "jsrt::function_id('".$this->phpid."')";
    }
}
