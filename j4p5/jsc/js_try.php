<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

use Walterra\J4p5Bundle\j4p5\jsc\js_construct;
use Walterra\J4p5Bundle\j4p5\jsc;
use Walterra\J4p5Bundle\j4p5\jsc\js_source;

class js_try extends js_construct {
    function __construct($code, $catch = NULL, $final = NULL) {
        list($this->body, $this->catch, $this->final) = func_get_args();
        $this->id_try = jsc::gensym("jsrt_try");
        $this->id_catch = jsc::gensym("jsrt_catch");
        $this->id_finally = jsc::gensym("jsrt_finally");
    }
    function toplevel_emit() {
        $o  = "function ".$this->id_try."() {\n";
        $o .= "  try ";
        $o .= trim(str_replace("\n", "\n  ", $this->body));    
        $o .= " catch (Exception \$e) {\n";
        $o .= "    jsrt::\$exception = \$e;\n";
        $o .= "  }\n";
        $o .= "  return NULL;\n";
        $o .= "}\n";
        if ($this->catch != NULL) {
            $o .= "function ".$this->id_catch."() {\n";
            $o .= "  ".trim(str_replace("\n", "\n  ", $this->catch));
            $o .= "\n  return NULL;\n";
            $o .= "}\n";
        }
        if ($this->final != NULL) {
            $o .= "function ".$this->id_finally."() {\n";
            $o .= "  ".trim(str_replace("\n", "\n  ", $this->final));
            $o .= "\n  return NULL;\n";
            $o .= "}\n";
        }
        return $o;
    }
    function emit($w=0) {
        // so we put catch() and finally blocks in functions to be able to pick if/when to evaluate them
        // it's not clear why try is in a function too at this point. consistency? yeah, weak.
        js_source::addFunctionDefinition($this);
        $id = ($this->catch!=NULL)?$this->catch->id:'';
        $this->body = $this->body->emit(1);
        if ($this->catch!=NULL) $this->catch = $this->catch->emit(1);
        if ($this->final!=NULL) $this->final = $this->final->emit(1);
        $ret = jsc::gensym("jsrt_ret");
        $tmp = jsc::gensym("jsrt_tmp");

        // try is on its own to work around a crash in my version of php5
        // apparently, php exceptions inside func_user_call()ed code are not all that stable just yet.
        // XXX note: the crash can still occur. still not entirely sure how it happens.
        // it feels like exceptions thrown from call_user_func-ed code corrupt some php internals, which
        // result in a possible crash at a later point in the program flow.
        $o  = "\$$tmp = ".$this->id_try."();\n";
        $o .= "\$$ret = jsrt::trycatch(\$$tmp, ";
        $o .= ($this->catch!=NULL?"'".$this->id_catch."'":"NULL").", ";
        $o .= ($this->final!=NULL?"'".$this->id_finally."'":"NULL");
        $o .= ($this->catch!=NULL?", '".$id."'":"").");\n";
        $o .= "if (\$$ret != NULL) return \$$ret;\n";
        return $o;
    }
}

?>