<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc\js_program;
use Walterra\J4p5Bundle\j4p5\jsc\js_var;

class js_source extends js_construct {
    function __construct($statements=array(), $functions=array()) {
        $this->code = $statements;
        $this->functions = $functions;
        $this->vars = array();
        $this->funcdef = array(); // only used by toplevel object
    }

    function addStatement($statement) {
        $this->code[]=$statement;
    }

    function addFunction($function) {
        $this->functions[] = $function;
    }

    static public $that;
    static public $nest;
    static public $labels;

    function addVariable($var) {
        js_source::$that->vars[] = $var;
    }

    function addFunctionExpression($function) {
        js_source::$that->functions[] = $function;
    }

    static public function addFunctionDefinition($function) {
        js_program::$source->funcdef[] = $function;
    }

    function emit($w=0) {
        self::$nest = 0;
        self::$labels = array();
        $o = '';    
        #dump the main body
        $saved_that = js_source::$that;
        js_source::$that = $this;
        $s = '';
        foreach ($this->code as $statement) {
            $s .= $statement->emit(1);
        }
        js_source::$that = $saved_that;
        #dump variable declarations now that we went through the body
        $v = js_var::really_emit($this->vars);
        #dump function expressions.
        $f = '';
        foreach ($this->functions as $function) {   
            $f .= $function->function_emit();
        }
        if ($f!='') $f = "/* function mapping */\n".$f;
        #if toplevel, dump function declarations
        $fd = "";
        if ($this === js_program::$source) {
            $fd = '';
            foreach ($this->funcdef as $function) {
                $fd .= $function->toplevel_emit();
            }
            if ($fd!='') $fd = "/* function declarations */\n".$fd;
        }
        # that's all folks
        return $f.$v.$s."\n}".$fd;
    }
}

