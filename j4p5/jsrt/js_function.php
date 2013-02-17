<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\output;
use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

class js_function extends js_object {
    protected $name;
    protected $phpname;
    protected $args;
    protected $scope = array();
    static $constructor;
    function __construct($name='', $phpname='jsi_empty', $args = array(), $scope = NULL) {
        parent::__construct("Function", jsrt::$proto_function);
        if ($scope == NULL) $scope = jsrt::$contexts[0]->scope_chain;
        $this->name = $name;
        $this->phpname = $phpname;
        $this->args = $args;
        $this->scope = $scope;
        $this->put("length", new js_val(js_val::NUMBER, count($args)), array("dontdelete","readonly","dontenum"));
        $obj = new js_object("Object");
        $obj->put("constructor", $this, array("dontenum"));
        $this->put("prototype", $obj, array("dontdelete"));
    }
    function construct($args) {
        $obj = new js_object("Object");
        $proto = $this->get("prototype");
        if ($proto->type == js_val::OBJECT) {
            $obj->prototype = $proto;
        } else {
            $obj->prototype = jsrt::$proto_object;
        }
        #-- [[Call]]
            $v = $this->_call($obj, $args,1);
        if ($v and $v->type==js_val::OBJECT) return $v;
        return $obj;
    }
    function defaultValue($iggy=NULL) {
        $o = "function ".$this->name."(";
        $o.= implode(",",$this->args);
        $o.=") {\n";
            $o.=" [ function body ] \n";
            $o.="}\n";
            return jss::js_str($o);
        }
        /* When the [[Call]] property for a Function object F is called, the following steps are taken:
        1. Establish a new execution context using F's FormalParameterList, the passed arguments list, and the this value as described in 10.2.3.
        2. Evaluate F's FunctionBody.
        3. Exit the execution context established in step 1, restoring the previous execution context.
        */
    function _call($that, $args=array(), $constructor=0) {
        js_function::$constructor = $constructor;
        #-- new activation object
            $var = new js_object("Activation");
        #-- populate stuff
            $arguments = new js_object();
        $var->put("arguments", $arguments);
        $len = count($args);
        for ($i=0; $i<count($this->args); $i++) {
            if (!isset($args[$i])) {
                $args[$i] = jsrt::$undefined;
            } else {
                if ($args[$i] instanceof js_ref) {
                    echo "<pre>";
                    echo "js_ref as $i-th argument of call\n";
                    debug_print_backtrace();
                    echo "</pre>";
                }
                //$args[$i] = $args[$i]->getValue(); // we don't pass by reference
            }
            $var->put($this->args[$i], $args[$i]);
            #-- enforce the weird "changing one changes the other" rule
                $arguments->slots[$this->args[$i]] = $var->slots[$this->args[$i]];
            $arguments->slots[$i] = $var->slots[$this->args[$i]];
        }
        if ($len>count($this->args)) {
            #-- unnammed extra arguments
            for ($i=count($this->args);$i<$len;$i++) {
                $arguments->put($i, $args[$i]);
            }
        }
        $arguments->put("callee", $this, array("dontenum"));
        $arguments->put("length", new js_val(js_val::NUMBER, $len), array("dontenum"));
        $scope = $this->scope;
        array_unshift($scope, $var);
        #-- new context
            $context = new js_context($that, $scope, $var);
        array_unshift(jsrt::$contexts, $context);
        $thrown = NULL;
        // echo "function name=".serialize($this->phpname)." arguments = ".serialize($args)."<hr>";
        try {
            // gross hack to hide warnings triggered by exception throwing.
            // this way, we still get to see other kind of errors. unless they're warnings. sigh.
            // note: this call_user_func_array() is responsible for crashes if exceptions are thrown through it.
            //$saved = error_reporting(4093);
            if(!is_array($this->phpname))
            {
                $out = output::getInstance(); // helper to get namespace/classname of dynamic php
                $this->phpname = array(
                    $out->getClassName(),
                    $this->phpname
                    );
                }
                if($this->phpname[0]=='jsrt') $this->phpname[0] = "Walterra\J4p5Bundle\j4p5\jsrt";
                if($this->phpname[0]=='js_object') $this->phpname[0] = "Walterra\J4p5Bundle\j4p5\jsrt\js_object";
                if($this->phpname[0]=='js_math') $this->phpname[0] = "Walterra\J4p5Bundle\j4p5\jsrt\js_math";
                if($this->phpname[0]=='js_string') $this->phpname[0] = "Walterra\J4p5Bundle\j4p5\jsrt\js_string";
                // this is sort of a hacky function we define to access data after the js was executed
                if($this->phpname[1]=='js_output') $this->phpname[0] = "Walterra\J4p5Bundle\j4p5\js";

                $v = call_user_func_array($this->phpname, $args);
                //error_reporting($saved);
            } catch (Exception $e) {
                $thrown = $e;
                //error_reporting($saved);
            }
            array_shift(jsrt::$contexts);
            // we restored context, time to follow-through on those exceptions.
            if ($thrown != NULL) {
                throw $thrown;
            }
            return $v?$v:jsrt::$undefined;
        }
        function hasInstance($value) {
            if ($value->type != js_val::OBJECT) return jsrt::$false;
            $obj = $this->get("prototype");
            if ($obj->type != js_val::OBJECT) throw new js_exception(new js_typeerror('XXX'));
            do {
                $value = $value->prototype;
                if ($value == NULL) return jsrt::$false;
                if ($obj == $value) return jsrt::$true;
            } while (true);
        }
        static function isConstructor() { 
            return self::$constructor; 
        }

        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static public function func_object($value) {
            throw new js_exception(new js_syntaxerror("new Function(..) not implemented"));
        }
        static public function func_toString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_function") throw new js_exception(new js_typeerror());
            return $obj->defaultValue();
        }
        static public function func_apply($thisArg, $argArray) {
            $obj = jsrt::$this();
            if (js::get_classname_without_namespace($obj)!="js_function") throw new js_exception(new js_typeerror());
            if ($thisArg==jsrt::$null or $thisArg==jsrt::$undefined) {
                $thisArg = jsrt::$global;
            } else {
                $thisArg = $thisArg->toObject();
            }
            if ($argArray=jsrt::$null or $argArray==jsrt::$undefined) {
                $argArray = array();
            } else {
                // check for a length property
                if ($argArray->hasProperty("length")) {
                    $argArray = js_array::toNativeArray($argArray);
                } else {
                    throw new js_exception(new js_typeerror("second argument to apply() must be an array"));
                }
            }
            return $obj->_call($thisArg, $argArray);
        }
        static public function func_call($thisArg) {
            $obj = jsrt::$this();
            if (js::get_classname_without_namespace($obj)!="js_function") throw new js_exception(new js_typeerror());
            $args = func_get_args();
            array_shift($args);
            if ($thisArg==jsrt::$null or $thisArg==jsrt::$undefined) {
                $thisArg = jsrt::$global;
            } else {
                $thisArg = $thisArg->toObject();
            }
            return $obj->_call($thisArg, $args);
        }

    }

