<?php

namespace Walterra\J4p5Bundle\j4p5;

use Exception;
use Walterra\J4p5Bundle\j4p5\js;
use Walterra\J4p5Bundle\j4p5\jss;
use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_val;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

// javascript runtime.
/* weird bugs I blame on the php engine:
- exceptions coming from internal functions would crash apache until I change the way I call
jsrt::trycatch(). I suspect php5 exceptions don't cross over call_user_func boundaries entirely correctly.
- when called within a method of class A that happens to have a method C,
call_user_func_array(array("B","C",array(...)) will end up calling A::C. blah.
(in my case, A was a subclass of B, which is not excuse whatsoever, but could be related.)
The work-around was to rename conflictual methods in A, which messes with my naming consistency.
*/


error_reporting(4095);

class jsrt {

    static $contexts;
    #-- collection of functions. useful for lambda expressions
        static $functions;
    static $global;
    static $zero;
    static $one;
    static $true;
    static $false;
    static $null;
    static $undefined;
    static $nan;
    static $infinity;
    #-- holds the current unprocessed exception
        static $exception;
    #-- holds the current sorting function used by Array::sort
        static $sortfn;
    #-- standard prototypes
        static $proto_object;
    static $proto_function;
    static $proto_array;
    static $proto_string;
    static $proto_boolean;
    static $proto_number;
    static $proto_date;
    static $proto_regexp;
    static $proto_error;
    static $proto_evalerror;
    static $proto_rangeerror;
    static $proto_referenceerror;
    static $proto_syntaxerror;
    static $proto_typeerror;
    static $proto_urierror;

    static function start_once() {
        if (js::get_classname_without_namespace(jsrt::$global)=="js_object") return;
        jsrt::start();
    }

    static function start() {
        #-- create a global object
            jsrt::$global = new js_object();
        #-- create the first execution context
            jsrt::$contexts = array(new js_context(jsrt::$global, array(jsrt::$global), jsrt::$global));
        #-- set a few things  
            jsrt::$functions = array();
        jsrt::$nan = jss::js_int(acos(1.01));
        jsrt::$infinity = jss::js_int(-log(0));
        jsrt::$undefined = new js_val(js_val::UNDEFINED,0);
        jsrt::$null = new js_val(js_val::NULL, 0);
        jsrt::$true = new js_val(js_val::BOOLEAN, true);
        jsrt::$false = new js_val(js_val::BOOLEAN, false);
        jsrt::$zero = jss::js_int(0);
        jsrt::$one = jss::js_int(1);
        jsrt::$exception = NULL;
        jsrt::$sortfn = NULL;

        $internal = array("dontenum","dontdelete","readonly");
        #-- common prototypes
            jsrt::$proto_object = jsrt::$proto_function = jsrt::$proto_array = jsrt::$proto_string = jsrt::$proto_boolean = jsrt::$proto_number = jsrt::$proto_date = jsrt::$proto_regexp = jsrt::$proto_error = jsrt::$proto_evalerror = jsrt::$proto_rangeerror = jsrt::$proto_referenceerror = jsrt::$proto_syntaxerror = jsrt::$proto_typeerror = jsrt::$proto_urierror = null;

        jsrt::$proto_object = new js_object();
        jsrt::push_context(jsrt::$proto_object);
        jsrt::define_function(array("js_object","toString"),'toString');
        jsrt::define_function(array("js_object","toString"),'toLocaleString');
        jsrt::define_function(array("js_object","valueOf"),'valueOf');
        jsrt::define_function(array("js_object","hasOwnProperty"),"hasOwnProperty",array("value"));
        jsrt::define_function(array("js_object","isPrototypeOf"),"isPrototypeOf",array("value"));
        jsrt::define_function(array("js_object","propertyIsEnumerable"),"propertyIsEnumerable",array("value"));
        jsrt::pop_context();
        jsrt::$proto_function = new js_function();
        jsrt::push_context(jsrt::$proto_function);
        jsrt::define_function(array("js_function","func_toString"),'toString');
        jsrt::define_function(array("js_function","func_apply"),'apply',array("thisArg","argArray"));
        jsrt::define_function(array("js_function","func_call"),'call',array("thisArg"));
        jsrt::pop_context();
        jsrt::$proto_array = new js_array();
        jsrt::push_context(jsrt::$proto_array);
        jsrt::define_function(array("js_array","toString"),'toString');
        jsrt::define_function(array("js_array","toLocaleString"),'toLocaleString');
        jsrt::define_function(array("js_array","concat"),"concat",array("item"));
        jsrt::define_function(array("js_array","join"),"join",array("separator"));
        jsrt::define_function(array("js_array","pop"),"pop");
        jsrt::define_function(array("js_array","push"),"push",array("item"));
        jsrt::define_function(array("js_array","reverse"),"reverse");
        jsrt::define_function(array("js_array","shift"),"shift");
        jsrt::define_function(array("js_array","slice"),"slice",array("start","end"));
        jsrt::define_function(array("js_array","sort"),"sort",array("comparefn"));
        jsrt::define_function(array("js_array","splice"),"splice",array("start","deleteCount"));
        jsrt::define_function(array("js_array","unshift"),"unshift",array("item"));
        jsrt::pop_context();
        jsrt::$proto_string = new js_string();
        jsrt::push_context(jsrt::$proto_string);
        jsrt::define_function(array("js_string","toString"),'toString');
        jsrt::define_function(array("js_string","toString"),'valueOf');
        jsrt::define_function(array("js_string","charAt"),'charAt',array("pos"));
        jsrt::define_function(array("js_string","charCodeAt"),'charCodeAt',array("pos"));
        jsrt::define_function(array("js_string","concat"),'concat',array("string"));
        jsrt::define_function(array("js_string","indexOf"),'indexOf',array("searchString"));
        jsrt::define_function(array("js_string","lastIndexOf"),'lastIndexOf',array("searchString"));
        jsrt::define_function(array("js_string","localeCompare"),'localeCompare',array("that"));
        jsrt::define_function(array("js_string","match"),'match',array("regexp"));
        jsrt::define_function(array("js_string","replace"),'replace',array("searchValue","replaceValue"));
        jsrt::define_function(array("js_string","search"),'search',array("regexp"));
        jsrt::define_function(array("js_string","slice"),'slice',array("start","end"));
        jsrt::define_function(array("js_string","split"),'split',array("separator","limit"));
        jsrt::define_function(array("js_string","substr"),'substr',array("start","length"));
        jsrt::define_function(array("js_string","substring"),'substring',array("start","end"));
        jsrt::define_function(array("js_string","toLowerCase"),'toLowerCase');
        jsrt::define_function(array("js_string","toLocaleLowerCase"),'toLocaleLowerCase');
        jsrt::define_function(array("js_string","toUpperCase"),'toUpperCase');
        jsrt::define_function(array("js_string","toLocaleUpperCase"),'toLocaleUpperCase');
        jsrt::pop_context();
        jsrt::$proto_boolean = new js_boolean();
        jsrt::push_context(jsrt::$proto_boolean);
        jsrt::define_function(array("js_boolean","toString"),'toString');
        jsrt::define_function(array("js_boolean","valueOf"),'valueOf');
        jsrt::pop_context();
        jsrt::$proto_number = new js_number();
        jsrt::push_context(jsrt::$proto_number);
        jsrt::define_function(array("js_number","toString"),'toString',array("radix"));
        jsrt::define_function(array("js_number","toString"),'toLocaleString',array("radix"));
        jsrt::define_function(array("js_number","valueOf"),'valueOf');
        jsrt::define_function(array("js_number","toFixed"),'toFixed',array("fractionDigits"));
        jsrt::define_function(array("js_number","toExponential"),'toExponential',array("fractionDigits"));
        jsrt::define_function(array("js_number","toPrecision"),'toPrecision',array("precision"));
        jsrt::pop_context();
        jsrt::$proto_date = new js_date();
        jsrt::push_context(jsrt::$proto_date);
        jsrt::define_function(array("js_date","toString"),'toString');
        jsrt::define_function(array("js_date","toDateString"),'toDateString');
        jsrt::define_function(array("js_date","toTimeString"),'toTimeString');
        jsrt::define_function(array("js_date","toLocaleString"),'toLocaleString');
        jsrt::define_function(array("js_date","toLocaleDateString"),'toLocaleDateString');
        jsrt::define_function(array("js_date","toLocaleTimeString"),'toLocaleTimeString');
        jsrt::define_function(array("js_date","valueOf"),'valueOf');
        jsrt::define_function(array("js_date","getTime"),'getTime');
        jsrt::define_function(array("js_date","getFullYear"),'getFullYear');
        jsrt::define_function(array("js_date","getUTCFullYear"),'getUTCFullYear');
        jsrt::define_function(array("js_date","getMonth"),'getMonth');
        jsrt::define_function(array("js_date","getUTCMonth"),'getUTCMonth');
        jsrt::define_function(array("js_date","getDate"),'getDate');
        jsrt::define_function(array("js_date","getUTCDate"),'getUTCDate');
        jsrt::define_function(array("js_date","getDay"),'getDay');
        jsrt::define_function(array("js_date","getUTCDay"),'getUTCDay');
        jsrt::define_function(array("js_date","getHours"),'getHours');
        jsrt::define_function(array("js_date","getUTCHours"),'getUTCHours');
        jsrt::define_function(array("js_date","getMinutes"),'getMinutes');
        jsrt::define_function(array("js_date","getUTCMinutes"),'getUTCMinutes');
        jsrt::define_function(array("js_date","getSeconds"),'getSeconds');
        jsrt::define_function(array("js_date","getUTCSeconds"),'getUTCSeconds');
        jsrt::define_function(array("js_date","getMilliseconds"),'getMilliseconds');
        jsrt::define_function(array("js_date","getUTCMilliseconds"),'getUTCMilliseconds');
        jsrt::define_function(array("js_date","getTimezoneOffset"),'getTimezoneOffset');
        jsrt::define_function(array("js_date","setTime"),'setTime',array("time"));
        jsrt::define_function(array("js_date","setMilliseconds"),'setMilliseconds',array("ms"));
        jsrt::define_function(array("js_date","setUTCMilliseconds"),'setUTCMilliseconds',array("ms"));
        jsrt::define_function(array("js_date","setSeconds"),'setSeconds',array("sec","ms"));
        jsrt::define_function(array("js_date","setUTCSeconds"),'setUTCSeconds',array("sec","ms"));
        jsrt::define_function(array("js_date","setMinutes"),'setMinutes',array("min","sec","ms"));
        jsrt::define_function(array("js_date","setUTCMinutes"),'setUTCMinutes',array("min","sec","ms"));
        jsrt::define_function(array("js_date","setHours"),'setHours',array("hour","min","sec","ms"));
        jsrt::define_function(array("js_date","setUTCHours"),'setUTCHours',array("hour","min","sec","ms"));
        jsrt::define_function(array("js_date","setDate"),'setDate',array("date"));
        jsrt::define_function(array("js_date","setUTCDate"),'setUTCDate',array("date"));
        jsrt::define_function(array("js_date","setMonth"),'setMonth',array("month","date"));
        jsrt::define_function(array("js_date","setUTCMonth"),'setUTCMonth',array("month","date"));
        jsrt::define_function(array("js_date","setFullYear"),'setFullYear',array("year","month","date"));
        jsrt::define_function(array("js_date","setUTCFullYear"),'setUTCFullYear',array("year","month","date"));
        jsrt::define_function(array("js_date","toUTCString"),'toUTCString');
        jsrt::pop_context();
        jsrt::$proto_regexp = new js_regexp();
        jsrt::push_context(jsrt::$proto_regexp);
        jsrt::define_function(array("js_regexp","exec"),'exec',array("string"));
        jsrt::define_function(array("js_regexp","test"),'test',array("string"));
        jsrt::define_function(array("js_regexp","toString"),'toString');
        jsrt::pop_context();
        jsrt::$proto_error = new js_error();
        jsrt::$proto_error->put("name",jss::js_str("Error"));
        jsrt::$proto_error->put("message",jss::js_str(""));
        jsrt::push_context(jsrt::$proto_error);
        jsrt::define_function(array("js_error","toString"),'toString');
        jsrt::pop_context();
        jsrt::$proto_evalerror = new js_evalerror();
        jsrt::$proto_evalerror->put("name",jss::js_str("EvalError"));
        jsrt::$proto_evalerror->put("message",jss::js_str(""));
        jsrt::$proto_rangeerror = new js_rangeerror();
        jsrt::$proto_rangeerror->put("name",jss::js_str("RangeError"));
        jsrt::$proto_rangeerror->put("message",jss::js_str(""));
        jsrt::$proto_referenceerror = new js_referenceerror();
        jsrt::$proto_referenceerror->put("name",jss::js_str("ReferenceError"));
        jsrt::$proto_referenceerror->put("message",jss::js_str(""));
        jsrt::$proto_syntaxerror = new js_syntaxerror();
        jsrt::$proto_syntaxerror->put("name",jss::js_str("SyntaxError"));
        jsrt::$proto_syntaxerror->put("message",jss::js_str(""));
        jsrt::$proto_typeerror = new js_typeerror();
        jsrt::$proto_typeerror->put("name",jss::js_str("TypeError"));
        jsrt::$proto_typeerror->put("message",jss::js_str(""));
        jsrt::$proto_urierror = new js_urierror();
        jsrt::$proto_urierror->put("name",jss::js_str("URIError"));
        jsrt::$proto_urierror->put("message",jss::js_str(""));
        #-- populate standard objects
            jsrt::define_variable("NaN", jsrt::$nan);
        jsrt::define_variable("Infinity", jsrt::$infinity);
        jsrt::define_variable("undefined", jsrt::$undefined);

        jsrt::define_function("jsi_eval", "eval");
        jsrt::define_function("jsi_parseInt", "parseInt", array("str","radix"));
        jsrt::define_function("jsi_parseFloat", "parseFloat", array("str"));
        jsrt::define_function("jsi_isNaN", "isNaN", array("value"));
        jsrt::define_function("jsi_isFinite", "isFinite", array("value"));
        jsrt::define_function("jsi_decodeURI", "decodeURI");
        jsrt::define_function("jsi_decodeURIComponent", "decodeURIComponent");
        jsrt::define_function("jsi_encodeURI", "encodeURI");
        jsrt::define_function("jsi_encodeURIComponent", "encodeURIComponent");

        $o = jsrt::define_function(array("js_object", "object"), "Object", array("value"), jsrt::$proto_object);
        jsrt::$proto_object->put("constructor", $o);
        $o = jsrt::define_function(array("js_function","func_object"), "Function", array("value"), jsrt::$proto_function);
        jsrt::$proto_function->put("constructor", $o);
        $o = jsrt::define_function(array("js_array","object"), "Array", array("value"), jsrt::$proto_array);
        jsrt::$proto_array->put("constructor", $o);
        $o = jsrt::define_function(array("js_string","object"), "String", array("value"), jsrt::$proto_string);
        jsrt::push_context($o);
        jsrt::define_function(array("js_string","fromCharCode"),"fromCharCode",array("char"));
        jsrt::pop_context();
        jsrt::$proto_string->put("constructor", $o);
        $o = jsrt::define_function(array("js_boolean","object"), "Boolean", array("value"), jsrt::$proto_boolean);
        jsrt::$proto_boolean->put("constructor", $o);
        $o = jsrt::define_function(array("js_number","object"), "Number", array("value"), jsrt::$proto_number);
        $o->put("MAX_VALUE", jss::js_int(1.7976931348623157e308), $internal);
        $o->put("MIN_VALUE", jss::js_int(5e-324), $internal);
        $o->put("NaN", jsrt::$nan, $internal);
        $o->put("NEGATIVE_INFINITY", jsrt::expr_u_minus(jsrt::$infinity), $internal);
        $o->put("POSITIVE_INFINITY", jsrt::$infinity, $internal);
        jsrt::$proto_number->put("constructor", $o);
        $o = jsrt::define_function(array("js_date","object"), "Date", array("year","month","date","hours","minutes","seconds","ms"), jsrt::$proto_date);
        jsrt::push_context($o);
        jsrt::define_function(array("js_date","parse"), "parse", array("string"));
        jsrt::define_function(array("js_date","UTC"), "UTC", array("year","month","date","hours","minutes","seconds","ms"));
        jsrt::pop_context();
        jsrt::$proto_date->put("constructor", $o);  
        $o = jsrt::define_function(array("js_regexp","object"), "RegExp", array("pattern","flags"), jsrt::$proto_regexp);
        jsrt::$proto_regexp->put("constructor", $o);
        $o = jsrt::define_function(array("js_error","object"), "Error", array("message"), jsrt::$proto_error);
        jsrt::$proto_error->put("constructor", $o);
        $o = jsrt::define_function(array("js_evalerror","object"), "EvalError", array("message"), jsrt::$proto_evalerror);
        jsrt::$proto_evalerror->put("constructor", $o);
        $o = jsrt::define_function(array("js_rangeerror","object"), "RangeError", array("message"), jsrt::$proto_rangeerror);
        jsrt::$proto_rangeerror->put("constructor", $o);
        $o = jsrt::define_function(array("js_referenceerror","object"), "ReferenceError", array("message"), jsrt::$proto_referenceerror);
        jsrt::$proto_referenceerror->put("constructor", $o);
        $o = jsrt::define_function(array("js_syntaxerror","object"), "SyntaxError", array("message"), jsrt::$proto_syntaxerror);
        jsrt::$proto_syntaxerror->put("constructor", $o);
        $o = jsrt::define_function(array("js_typeerror","object"), "TypeError", array("message"), jsrt::$proto_typeerror);
        jsrt::$proto_typeerror->put("constructor", $o);
        $o = jsrt::define_function(array("js_urierror","object"), "URIError", array("message"), jsrt::$proto_urierror);
        jsrt::$proto_urierror->put("constructor", $o);
        $math = new js_math();
        jsrt::define_variable("Math", $math);
        $math->put("E", jss::js_int(M_E), $internal);
        $math->put("LN10", jss::js_int(M_LN10), $internal);
        $math->put("LN2", jss::js_int(M_LN2), $internal);
        $math->put("LOG2E", jss::js_int(M_LOG2E), $internal);
        $math->put("LOG10E", jss::js_int(M_LOG10E), $internal);
        $math->put("PI", jss::js_int(M_PI), $internal);
        $math->put("SQRT1_2", jss::js_int(M_SQRT1_2), $internal);
        $math->put("SQRT2", jss::js_int(M_SQRT2), $internal);
        jsrt::push_context($math);
        jsrt::define_function(array("js_math","abs"), "abs",array("x"));
        jsrt::define_function(array("js_math","acos"),"acos",array("x"));
        jsrt::define_function(array("js_math","asin"),"asin",array("x"));
        jsrt::define_function(array("js_math","atan"),"atan",array("x"));
        jsrt::define_function(array("js_math","atan2"),"atan2",array("y","x"));
        jsrt::define_function(array("js_math","ceil"),"ceil",array("x"));
        jsrt::define_function(array("js_math","cos"),"cos",array("x"));
        jsrt::define_function(array("js_math","exp"),"exp",array("x"));
        jsrt::define_function(array("js_math","floor"),"floor",array("x"));
        jsrt::define_function(array("js_math","log"),"log",array("x"));
        jsrt::define_function(array("js_math","max"),"max",array("value1","value2"));
        jsrt::define_function(array("js_math","min"),"min",array("value1","value2"));
        jsrt::define_function(array("js_math","pow"),"pow",array("x","y"));
        jsrt::define_function(array("js_math","random"),"random");
        jsrt::define_function(array("js_math","round"),"round",array("x"));
        jsrt::define_function(array("js_math","sin"),"sin",array("x"));
        jsrt::define_function(array("js_math","sqrt"),"sqrt",array("x"));
        jsrt::define_function(array("js_math","tan"),"tan",array("x"));
        jsrt::pop_context();
        // extensions to the spec. 
        jsrt::define_variable("global", jsrt::$global);
        jsrt::define_function(array("jsrt","write"), "write");
        jsrt::define_function(array("jsrt","write"), "print");
    }

    static function push_context($obj) {
        array_unshift(jsrt::$contexts, new js_context(jsrt::$contexts[0]->js_this, jsrt::$contexts[0]->scope_chain, $obj));
    }
    static function pop_context() {
        array_shift(jsrt::$contexts);
    }

    static function push_scope($obj) {
        array_unshift(jsrt::$contexts[0]->scope_chain, $obj);
        jsrt::$idcache=array();
    }
    static function pop_scope() {
        array_shift(jsrt::$contexts[0]->scope_chain);
        jsrt::$idcache=array();
    }

    static function define_function($phpname, $jsname='', $args=array(), $proto = NULL) {
        $func = new js_function($jsname, $phpname, $args, jsrt::$contexts[0]->scope_chain);
        if ($proto!=NULL) {
            $func->put("prototype", $proto, array("dontenum","dontdelete","readonly"));
        }
        jsrt::$contexts[0]->var->put($jsname, $func);
        if (is_string($phpname)) {
            jsrt::$functions[$phpname] = $func;
        }
        return $func;
    }

    static function define_variable($name, $val=NULL) {
        if ($val==NULL) $val=jsrt::$undefined;
        jsrt::$contexts[0]->var->put($name, $val);
    }
    static function define_variables() {
        $args = func_get_args();
        foreach ($args as $arg) { jsrt::define_variable($arg); }
    }

    static function trycatch($expr, $catch, $finally, $id=0) {
        if (js_thrown(jsrt::$exception)) {
            #-- assert($expr == NULL);
            if ($expr != NULL) {
                echo "TRYCATCH ERROR: INCONSISTENT STATE.<hr><br>";
            }
            /* evaluate catch */
            if ($catch!=NULL) {
                $obj = new js_object();
                $obj->put($id, jsrt::$exception->value, array("dontdelete"));
                jsrt::$exception = NULL;
                jsrt::push_scope($obj);
                $ret = $catch();
                jsrt::pop_scope();      
                if ($ret != NULL) $expr = $ret;
            }
        }
        if ($finally!=NULL) {
            #-- XXX tentative workaround for the call_user_func + exception crash in 5.0.3
                $ret = $finally();
            if ($ret != NULL) $expr = $ret;
        }
        if (js_thrown(jsrt::$exception)) {
            throw jsrt::$exception; #-- pass it down.
        }
        return $expr;
    }

    static function call($method, $args) {
        // not fully compliant with 11.2.3 XXX
        if ($method instanceof js_ref) {
            $that = $method->base;
            if (!$that) {
                //echo '['.get_class($that).'->'.$method->propName.']';
                $that = jsrt::$global;
                $method->base = $that;
            }
            $obj = $method->getValue();
        } else {
            $that = jsrt::$global;
            $obj = $method;
        }
        // ok, call [[Call]]
        if (!$obj) return jsrt::$undefined;
        if ($obj instanceof js_function) { // XXX there could be other "callable" objects. maybe.
            jsrt::$idcache=array();
            $ret = $obj->_call($that, $args);
            jsrt::$idcache=array();
            return $ret;
        } else {
            throw new js_exception(new js_typeerror("Cannot call an object that isn't a function"));
        }
    }

    static function _new($constructor, $args) {
        $c = $constructor; //->getValue();
        if (!($c instanceof js_function)) {
            throw new js_exception(new js_syntaxerror("invalid constructor"));
        }
        return $c->construct($args);
    }

    /* resolve an identifier into a js_ref object */
    static $idcache;
    static function id($id) {
        if (!isset(jsrt::$idcache[$id])) {
            #-- get scope chain
                $chain = jsrt::$contexts[0]->scope_chain;
            foreach ($chain as $scope) {
                if ($scope->hasProperty($id)) {
                    /*
                    if (isset(jsrt::$idcache[$id]) and jsrt::$idcache[$id]->base != $scope) {
                    echo "bad cache for $id..<br>";
                    echo "old scope = ".serialize(jsrt::$idcache[$id]->base)."<br>";
                    echo "new scope = ".serialize($scope)."<br>";
                    }
                    */
                    jsrt::$idcache[$id] = new js_ref($scope, $id);
                    return jsrt::$idcache[$id];

                }
            }
            return new js_ref_null($id);
        }
        return jsrt::$idcache[$id];
    }
    static function idv($id) {
        return jsrt::id($id)->getValue();
    }

    static function dot($base, $prop) {
        $obj = $base; //->getValue();
        if ($obj == jsrt::$null) {
            echo "dot(NULL, xxx) DOES NOT COMPUTE. ABORT! <pre>";
            debug_print_backtrace();
            echo "</pre>";
        }
        if (!($prop instanceof js_ref)) {
            $base = $prop->toStr()->value;
        } else {
            $base = $prop->propName;
        }
        //echo "Computing ".get_class($obj->toObject())."->".$base."<br>";
        //jsrt::debug($obj);
        return new js_ref($obj->toObject(), $base);
    }

    static function debug($obj) {
        if (is_object($obj))
            echo $obj->toDebug();
        else 
            echo "[NOTANOBJECT=".$obj."]";
    }

    static function dotv($base, $prop) {
        //echo @"DOTV(base, ".$prop->propName.")<br>";
        return jsrt::dot($base, $prop)->getValue();
    }

    static function function_id($phpname) {
        if (isset(jsrt::$functions[$phpname])) return jsrt::$functions[$phpname];
        return jsrt::$undefined;
    }

    static function literal_array() {
        $args = func_get_args();
        $array = new js_array();
        foreach ($args as $arg) {
            $array->_push($arg);
        }
        return $array;
    }

    static function literal_object() {
        $args = func_get_args();
        $obj = new js_object();
        for ($i=0;$i<count($args);$i+=2) {
            $obj->put($args[$i]->value, $args[$i+1]);
        }
        return $obj;
    }

    static function this() {
        $t = jsrt::$contexts[0]->js_this;
        if ($t) return $t;
        return jsrt::$global;
    }

    static function expr_assign($left, $right, $op = NULL) {
        return $left->putValue( ($op==NULL)?$right:jsrt::$op($left->getValue(), $right), 1);
    }

    static function expr_comma($a, $b) {
        return $b;
    }

    static function expr_plus($a, $b) {
        $a = $a->toPrimitive();
        $b = $b->toPrimitive();
        if ($a->type==js_val::STRING or $b->type==js_val::STRING) {
            $a = $a->toStr();
            $b = $b->toStr();
            return jss::js_str($a->value . $b->value);
        } else {
            $a = $a->toNumber();
            $b = $b->toNumber();
            return jss::js_int($a->value + $b->value);
        }
    }
    static function expr_minus($a, $b) {
        return jss::js_int($a->toNumber()->value - $b->toNumber()->value);
    }
    static function expr_divide($a, $b) {
        $a = $a->toNumber()->value;
        $b = $b->toNumber()->value;
        if (is_nan($a) or is_nan($b)) return jsrt::$nan;
        if (is_infinite($a) and is_infinite($b)) return jsrt::$nan;
        if (is_infinite($a)) return jsrt::$infinity; // wrong sign XXX
        if (is_infinite($b)) return jsrt::$zero;
        if ($a==0 and $b==0) return jsrt::$nan;
        if ($b==0) return jsrt::$infinity; // wrong sign. again. XXX
        return @jss::js_int($a / $b);
    }
    static function expr_multiply($a, $b) {
        return jss::js_int($a->toNumber()->value * $b->toNumber()->value);
    }
    static function expr_modulo($a, $b) {
        return jss::js_int($a->toNumber()->value % $b->toNumber()->value);
    }
    static function expr_post_pp($a) {
        return $a->putValue(jss::js_int($a->getValue()->toNumber()->value+1), 2);
    }
    static function expr_post_mm($a) {
        return $a->putValue(jss::js_int($a->getValue()->toNumber()->value-1), 2);
    }
    static function expr_delete($a) {
        if (!($a instanceof js_ref)) return jsrt::$true;
        // clear the idcache
        jsrt::$idcache = array();
        return $a->base->delete($a->propName);
    }
    static function expr_void($a) {
        return jsrt::$undefined;
    }
    static function expr_typeof($a) {
        if ($a instanceof js_ref) {
            if ($a->base == NULL) return jsrt::$undefined;
        }
        $a = $a->getValue();
        switch($a->type) {
            case js_val::UNDEFINED: return jss::js_str("undefined");
            case js_val::NULL: return jss::js_str("object");
            case js_val::BOOLEAN: return jss::js_str("boolean");
            case js_val::NUMBER: return jss::js_str("number");
            case js_val::STRING: return jss::js_str("string");
            case js_val::OBJECT: 
            if ($a instanceof js_function) {
                return jss::js_str("function");
            } else {
                return jss::js_str("object");
            }
        }
        return jss::js_str("unknown"); // inspired by IE, or something
    }
    static function expr_pre_pp($a) {
        $v = $a->getValue()->toNumber();
        $v = jss::js_int($v->value + 1);
        $a->putValue($v);
        return $v;
    }
    static function expr_pre_mm($a) {
        $v = $a->getValue()->toNumber();
        $v = jss::js_int($v->value - 1);
        $a->putValue($v);
        return $v;
    }
    static function expr_u_plus($a) {
        return $a->toNumber();
    }
    static function expr_u_minus($a) {
        $v = $a->toNumber();
        if (!is_nan($v->value)) {
            $v = jss::js_int(-$v->value);
        }
        return $v;
    }
    static function expr_bit_not($a) {
        return jss::js_int(~$a->toInt32()->value);
    }
    static function expr_not($a) {
        return ($a->toBoolean()->value)?jsrt::$false:jsrt::$true;
    }
    static function expr_lsh($a, $b) {
        $a = $a->toInt32();
        $b = $b->toUInt32();
        $v = jss::js_int($a->value << ($b->value & 0x1F));
        return $v;
        // XXX potential problem here. $v may be bigger than 32 bits.
    }
    static function expr_rsh($a, $b) {
        return jss::js_int($a->toInt32()->value >> ($b->toUInt32()->value & 0x1F));
    }
    static function expr_ursh($a, $b) {
        $a = $a->toInt32()->value;
        $b = $b->toUInt32()->value;
        $i = $a >> ($b & 0x1F);
        // now I need to zero the b-th highest bits.
        $k = 0x80000000;
        for ($c=0;$c<$b;$c++) {
            $i &= ~$k;
            $k >>= 1;
        }
        // pretty freaking slow. XXX think of a faster way.
        return jss::js_int($i);
    }
    static function expr_lt($a, $b) {
        return jsrt::cmp($a, $b, 1);
    }
    static function expr_gt($a, $b) {
        return jsrt::cmp($b, $a, 1);
    }
    static function expr_lte($a, $b) {
        $v = jsrt::cmp($b, $a);
        if ($v == jsrt::$true or $v == jsrt::$undefined) return jsrt::$false;
        return $v;
    }
    static function expr_gte($a, $b) {
        $v = jsrt::cmp($a, $b);
        if ($v == jsrt::$true or $v == jsrt::$undefined) return jsrt::$false;
        return $v;
    }
    static function cmp($a, $b, $f=0) {
        $a = $a->toPrimitive(js_val::NUMBER);
        $b = $b->toPrimitive(js_val::NUMBER);
        if ($a->type == js_val::STRING and $b->type == js_val::STRING) {
            if (strpos($a->value, $b->value)===0) return jsrt::$false;
            if (strpos($b->value, $a->value)===0) return jsrt::$true;
            return ($a<$b)?jsrt::$true:jsrt::$false; // XXX may not be 100% correct with 11.8.5.[18-21]
        } else {
            $a = $a->toNumber();
            $b = $b->toNumber();
            if (is_nan($a->value) or is_nan($b->value)) return $f?jsrt::$false:jsrt::$undefined;
            if ($a->value == $b->value) return jsrt::$false;
            /*
            if ($a->value>0 and is_infinite($a->value)) return jsrt::$false;
            if ($b->value>0 and is_infinite($b->value)) return jsrt::$true;
            if ($b->value<0 and is_infinite($b->value)) return jsrt::$false;
            if ($a->value<0 and is_infinite($a->value)) return jsrt::$true;
            */
            return ($a->value<$b->value)?jsrt::$true:jsrt::$false; // XXX 11.8.5.15
        }
    }
    static function expr_instanceof($a, $b) {
        if ($b->type != js_val::OBJECT) {
            echo "ERROR: TypeError Exception at line ".__LINE__." in file ".__FILE__."<hr>";
            return jsrt::$undefined;
        }
        return $b->hasInstance($a);
    }
    static function expr_in($a, $b) {
        if ($b->type != js_val::OBJECT) {
            echo "ERROR: TypeError Exception at line ".__LINE__." in file ".__FILE__."<hr>";
            return jsrt::$undefined;
        }
        $a = $a->toStr();
        return $b->hasProperty($a);
    }
    static function expr_equal($a, $b) {
        return jsrt::abstract_equal($a, $b);
    }
    static function expr_not_equal($a, $b) {
        return jsrt::abstract_equal($a, $b)->value?jsrt::$false:jsrt::$true;
    }
    static function abstract_equal($a, $b) {
        if ($a->type != $b->type) {
            if ($a->type == js_val::UNDEFINED and $b->type == js_val::NULL) return jsrt::$true;
            if ($b->type == js_val::UNDEFINED and $a->type == js_val::NULL) return jsrt::$true;
            if ($a->type == js_val::NUMBER and $b->type == js_val::STRING) return jsrt::abstract_equal($a, $b->toNumber());
            if ($b->type == js_val::NUMBER and $a->type == js_val::STRING) return jsrt::abstract_equal($a->toNumber(), $b);
            if ($a->type == js_val::BOOLEAN) return jsrt::abstract_equal($a->toNumber(), $b);
            if ($b->type == js_val::BOOLEAN) return jsrt::abstract_equal($a, $b->toNumber());
            if ( ($a->type == js_val::NUMBER or $a->type == js_val::STRING) and $b->type == js_val::OBJECT) return jsrt::abstract_equal($a, $b->toPrimitive());
            if ( ($b->type == js_val::NUMBER or $b->type == js_val::STRING) and $a->type == js_val::OBJECT) return jsrt::abstract_equal($a->toPrimitive(), $b);
            return jsrt::$false;
        } else {
            if ($a->type == js_val::UNDEFINED) return jsrt::$true;
            if ($a->type == js_val::NULL) return jsrt::$true;
            if ($a->type == js_val::NUMBER) {
                if (is_nan($a->value) or is_nan($b->value)) return jsrt::$false;
            }
            if ($a->type == js_val::OBJECT) {
                return ($a === $b)?jsrt::$true:jsrt::$false;
            }
            return ($a->value == $b->value)?jsrt::$true:jsrt::$false;
        }
    }
    static function expr_strict_equal($a, $b) {
        $v = jsrt::strict_equal($a, $b);
        return $v;
    }
    static function expr_strict_not_equal($a, $b) {
        return jsrt::strict_equal($a, $b)->value?jsrt::$false:jsrt::$true;
    }
    static function strict_equal($a, $b) {
        if ($a->type != $b->type) return jsrt::$false;
        if ($a->type == js_val::UNDEFINED or $a->type == js_val::NULL) return jsrt::$true;
        if ($a->type == js_val::NUMBER) {
            if (is_nan($a->value) or is_nan($b->value)) return jsrt::$false;  
        }
        if ($a->type == js_val::OBJECT) {
            return ($a === $b)?jsrt::$true:jsrt::$false;
        }
        return ($a->value == $b->value)?jsrt::$true:jsrt::$false;  
    }
    static function expr_bit_and($a, $b) {
        return jss::js_int($a->toInt32()->value & $b->toInt32()->value);
    }
    static function expr_bit_xor($a, $b) {
        return jss::js_int($a->toInt32()->value ^ $b->toInt32()->value);
    }
    static function expr_bit_or($a, $b) {
        return jss::js_int($a->toInt32()->value | $b->toInt32()->value);
    }
    static function write() {
        $args = func_get_args();
        foreach ($args as $arg) {
            $s = $arg->toStr();
            echo $s->value;
        }
        //ob_flush();
        flush();
    }
} /* jsrt */

/* Execution Context representation */
class js_context {
    public $js_this;
    public $scope_chain;
    public $var;

    function __construct($that, $scope_chain, $var) {
        $this->js_this = $that;
        $this->scope_chain = $scope_chain;
        $this->var = $var;
    }
}

class js_exception extends Exception {
    const EXCEPTION = 7;
    const NORMAL = 8;
    public $type;
    public $value;
    function __construct($value) {
        parent::__construct();
        $this->type = self::EXCEPTION;
        $this->value = $value;
    }
}

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
                if($this->phpname[0]=='js_math') $this->phpname[0] = "Walterra\J4p5Bundle\j4p5\js_math";
                if($this->phpname[0]=='js_string') $this->phpname[0] = "Walterra\J4p5Bundle\j4p5\js_string";
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

    class js_array extends js_object {
        protected $length;
        function __construct($len=0,$args=array()) {
            parent::__construct("Array", jsrt::$proto_array);
            if ($len==0) $len = jsrt::$zero;
            $this->length = $len;
            foreach ($args as $index=>$value) {
                echo "Setting $index to $value<br>";
                $this->put($index, $value);
            }
        }
        function defaultValue($iggy=NULL) {
            $arr = array();
            for ($i=0;$i<$this->length->value;$i++) $arr[$i]='';
            foreach ($this->slots as $index=>$value) {
                if (is_numeric($index)) $arr[$index] = $value->value->toStr()->value;
            }
            $o = implode(",", $arr);
            return jss::js_str($o);
        }
        function get($name) {
            $name = strval($name);
            if ($name=="length") {
                return $this->length;
            } else
                return parent::get($name);
        }
        function put($name, $value, $opts=NULL) {
            $name = strval($name);
            //echo "Setting $name to ".serialize($value)."<br>";
            if ($name=="length") {
                //$value = $value->getValue();
                if ($value->value<$this->length->value) {
                    #-- truncate.
                    foreach ($this->slots as $index=>$value) {
                        if (is_numeric($index) and $index>=$value->value) {
                            $this->delete($index);
                        }
                    }
                }
                $this->length = $value;
            } else {
                if (is_numeric($name)) {
                    if ($name-0>=$this->length->value) {
                        $this->length = jss::js_int($name+1);
                    }
                }
                return parent::put($name, $value, $opts);
            }
        }
        function _push($val) {
            $v = $this->length->value;
            $this->put($v, $val);
            //$this->length = jss::js_int($v+1);
        }
        static function toNativeArray($obj) {
            $len = $obj->get("length")->value;
            $arr = array();
            for ($i=0;$i<$len;$i++) {
                $arr[$i] = $obj->get($i);
            }
            return $arr;
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static public function object($value) {
            if (func_num_args()==1 and $value->type == js_val::NUMBER and $value->toUInt32()->value == $value->value) {
                $obj = new js_array();
                $obj->put("length", $value);
                return $obj;
            }
            $contrived = func_get_args();
            return call_user_func_array(array("jsrt","literal_array"), $contrived);
        }
        static public function toString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_array") throw new js_exception(new js_typeerror());
            return $obj->defaultValue();
        }
        static public function toLocaleString() {
            // XXX write a localized version
            return js_array::toString();
        }
        static public function concat() {
            $array = new js_array();
            $args = func_get_args();
            array_unshift($args, jsrt::$this());
            while (count($args)>0) {
                $obj = array_shift($args);
                if (js::get_classname_without_namespace($obj)!="js_array") {
                    $array->_push($obj);
                } else {
                    $len = $obj->get("length")->value;
                    for ($k=0; $k<$len; $k++ ){
                        if ($obj->hasProperty($k)) {
                            $array->_push($obj->get($k));
                        }
                    }
                }
            }
            return $array;
        }
        static public function join($sep) {
            $obj = jsrt::this();
            $len = $obj->get("length")->toUInt32()->value;
            if ($sep==jsrt::$undefined) {
                $sep = ",";
            } else {
                $sep = $sep->toStr()->value;
            }
            if ($len==0) return jss::js_str("");
            $arr = js_array::toNativeArray($obj);
            $arr2 = array();
            foreach ($arr as $elem) {
                array_push($arr->toStr());
            }
            return jss::js_str(implode($sep, $arr2));
        }
        static public function pop() {
            $obj = jsrt::this();
            $len = $obj->get("length")->toUInt32();
            if ($len->value==0) {
                $obj->put("lengh", $len);
                return jsrt::$undefined;
            }
            $index = $len->value-1;
            $elem = $obj->get($index);
            $obj->delete($index);
            $obj->put("length",jss::js_int($index));
            return $elem;
        }
        static public function push() {
            $obj = jsrt::this();
            $n = $obj->get("length")->toUInt32()->value;
            $args = func_get_Args();
            while (count($args)>0) {
                $arg = array_shift($args);
                $obj->put($n, $arg);
                $n++;
            }
            $obj->put("length",jss::js_int($n));
            return $n;
        }
        static public function reverse() {
            $obj = jsrt::this();
            $len = $obj->get("length")->toUInt32()->value;
            $mid = floor($len/2);
            $k = 0;
            while ($k!=$mid) {
                $l = $len - $k -1;
                if (!$obj->hasProperty($k)) {
                    if (!$obj->hasProperty($l)) {
                        $obj->delete($k);
                        $obj->delete($l);
                    } else {
                        $obj->put($k, $obj->get($l));
                        $obj->delete($l);
                    }
                } else {
                    if (!$obj->hasProperty($l)) {
                        $obj->put($l, $obj->get($k));
                        $obj->delete($k);
                    } else {
                        $a = $obj->get($k);
                        $obj->put($k, $obj->get($l));
                        $obj->put($l, $a);
                    }
                }
                $k++;
            }
            return $obj;
        }
        static public function shift() {
            $obj = jsrt::this();
            $len = $obj->get("length")->toUInt32()->value;
            if ($len==0) {
                $obj->put("length", 0);
                return jsrt::$undefined;
            }
            $first = $obj->get(0);
            $k = 1;
            while ($k != $len) {
                if ($obj->hasProperty($k)) {
                    $obj->put($k-1, $obj->get($k));
                } else {
                    $obj->delete($k-1);
                }
                $k++;
            }
            $obj->delete($len-1);
            $obj->put("length",$len-1);
            return $first;
        }
        static public function slice($start, $end) {
            $obj = jsrt::this();
            $array = new js_array();
            $len = $obj->get("length")->toUInt32()->value;
            $start = $start->toInteger()->value;
            $k = ($start<0)?max($len+$start, 0):min($len,$start);
            if ($end==jsrt::$undefined) $end=$len; else $end=$end->toInteger()->value;
            $end = ($end<0)?max($len+$end,0):min($len,$end);
            $n = 0;
            while ($k<$end) {
                if ($obj->hasProperty($k)) {
                    $array->put($n, $obj->get($k));
                }
                $k++;
                $n++;
            }
            $array->put("length", $n);
            return $array;
        }
        static public function sort($comparefn) {
            $obj = jsrt::this();
            $arr = js_array::toNativeArray($obj);

            jsrt::$sortfn = $comparefn;
            usort($arr, array("js_array","sort_helper"));
            jsrt::$sortfn = NULL;
            $len = count($arr);
            for ($i=0;$i<$len;$i++) {
                $obj->put($i, $arr[$i]);
            }
            $obj->put('length',jss::js_int($len));
            return $obj;
        }
        static public function sort_helper($a, $b) {
            if ($a==jsrt::$undefined) {
                if ($b==jsrt::$undefined) {
                    return 0;
                } else {
                    return 1;
                }
            } else {
                if ($b==jsrt::$undefined) {
                    return -1;
                }
            }
            if (jsrt::$sortfn == NULL or jsrt::$sortfn == jsrt::$undefined) {
                $a = $a->toStr();
                $b = $b->toStr();
                if (js_bool(jsrt::expr_lt($a,$b))) return -1;
                if (js_bool(jsrt::expr_gt($a,$b))) return 1;
                return 0;
            } else {
                return jsrt::$sortfn->_call($a, $b)->toInteger()->value;
            }
        }
        static public function splice($start,$deleteCount) {
            $obj = jsrt::this();
            $args = func_get_args();
            array_shift($args);array_shift($args);
            $array = new js_array();
            $len = $obj->get("length")->toUInt32()->value;
            $start = $start->toInteger();
            $start = ($start<0)?max($len+$start,0):min($len,$start);
            $count = min(max($deleteCount->toInteger(),0),$len-$start);
            $k=0;
            while ($k!=$count) {
                if ($obj->hasProperty($start+$k)) {
                    $array->put($k, $obj->get($start+$k));
                }
                $k++;
            }
            $array->put("length",jss::js_int($count));
            $nbitems = count($args);
            if ($nbitems!=$count) {
                if ($nbitems<=$count) {
                    $k = $start;
                    while ($k!=$len-$count) {
                        $r22 = $k+$count;
                        $r23 = $k+$nbitems;
                        if ($obj->hasProperty($r22)) {
                            $obj->put($r23, $obj->get($r22));
                        } else {
                            $obj->delete($r23);
                        }
                        $k++;
                    }
                    $k = $len;
                    while ($k!=$len-$count+$nbitems) {
                        $obj->delete($k-1);
                        $k--;
                    }
                } else {
                    $k = $len - $count;
                    while ($k!=$start) {
                        $r39 = $k + $count -1;
                        $r40 = $k + $nbitems -1;
                        if ($obj->hasProperty($r39)) {
                            $obj->put($r40, $obj->get($r39));
                        } else {
                            $obj->delete($r40);
                        }
                        $k--;
                    }
                }
            }
            $k = $start;
            while (count($args)>0) {
                $obj->put($k++, array_shift($args));
            }
            $obj->put("length", jss::js_int($len - $count + $nbitems));
            return $array;
        }
        static public function unshift() {
            $obj = jsrt::this();
            $len = $obj->get("length")->toUInt32()->value;
            $args = func_get_args();
            $nbitems = count($args);
            $k = $len;
            while ($k!=0) {
                if ($obj->hasProperty($k-1)) {
                    $obj->put($k+$nbitems-1, $obj->get($k-1));
                } else {
                    $obj->delete($k+$nbitems-1);
                }
                $k--;
            }
            while (count($args)>0) {
                $obj->put($k, array_shift($args));
                $k++;
            }
            $obj->put("length", $len+$nbitems);
            return jss::js_int($len+$nbitems);
        }
    }

    class js_string extends js_object {
        function __construct($value=NULL) {
            parent::__construct("String", jsrt::$proto_string);
            if ($value==NULL or $value==jsrt::$undefined) {
                $this->value = jss::js_str("");
            } else {
                $this->value = $value->toStr();
            }    
            $len = strlen($this->value->value);
            if (jsrt::$proto_string != NULL) {
                $this->put("length", jss::js_int($len), array("dontenum","dontdelete","readonly"));
            }
        }
        function defaultValue($iggy=NULL) {
            return $this->value;
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static public function object($value) {
            if (js_function::isConstructor()) {
                return new js_string($value);
            } else {
                if ($value==jsrt::$undefined) return jss::js_str("");
                return $value->toStr();
            }
        }
        static public function fromCharCode($c) {
            $args = func_get_args();
            $s = '';
            foreach ($args as $arg) {
                $v = $arg->toUInt16()->value;
                $s .= chr($v); // XXX fails if $v>255
            }
            return jss::js_str($s);
        }
        static public function toString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_string") throw new js_exception(new js_typeerror());
            return $obj->value;
        }
        static public function charAt($pos) {
            $str = jsrt::this()->toStr()->value;
            $pos = $pos->toInteger()->value;
            if ($pos<0 || strlen($str)<=$pos) return jss::js_str("");
            return jss::js_str($str[$pos]);
        }
        static public function charCodeAt($pos) {
            $str = jsrt::this()->toStr()->value;
            $pos = $pos->toInteger()->value;
            if ($pos<0 || strlen($str)<=$pos) return jsrt::$nan;
            return jss::js_int(ord($str[$pos]));
        }
        static public function concat($str) {
            $str = jsrt::this()->toStr()->value;
            $args = func_get_args();
            foreach ($args as $arg) {
                $str .= $arg->toStr()->value;
            }
            return jss::js_str($str);
        }
        static public function indexOf($str, $pos) {
            $obj = jsrt::this()->toStr()->value;
            $str = $str->toStr()->value;
            $pos = $pos->toInteger()->value;
            $v = strpos($obj, $str, $pos);
            if ($v===FALSE) return jss::js_int(-1);
            return jss::js_int($v);
        }
        static public function lastIndexOf($str, $pos) {
            $obj = jsrt::this()->toStr()->value;
            $str = $str->toStr()->value;
            $pos = $pos->toNumber()->value;
            if (is_nan($pos)) $pos = strlen($obj);
            $v = strrpos($obj, $str, $pos);
            if ($v===FALSE) return jss::js_int(-1);
            return jss::js_int($v);
        }
        static public function localeCompare($that) {
            $obj = jsrt::this();
            return jss::js_int(strcoll($obj->toStr()->value, $that->toStr()->value));
        }
        static public function match($regexp) {
            $obj = jsrt::this()->toStr();
            if (js::get_classname_without_namespace($regexp)!="js_regexp") {
                $regexp = new js_regexp($regexp->toStr()->value);
            }
            if ($regexp->get("global")==jsrt::false) {
                return jsrt::$proto_regexp->get("exec")->_call($regexp, $obj);
            } else {
                $regexp->put("lastIndex", jsrt::$zero);
                // XXX finish once the RegExp stuff is written # 15.5.4.10 
                throw new js_exception(new js_error("string::match not implemented"));
            }
        }
        static public function replace($search, $replace) {
            $obj = jsrt::this()->toStr();
            // XXX finish once the Regexp stuff is written
            throw new js_exception(new js_error("string::replace not implemented"));
        }
        static public function search($regexp) {
            $obj = jsrt::this()->toStr();
            if (js::get_classname_without_namespace($regexp)!="js_regexp") {
                $regexp = new js_regexp($regexp->toStr()->value);
            }
            // XXX finish once RegExp is there
            throw new js_exception(new js_error("string::search not implemented"));
        }
        static public function slice($start, $end) {
            $obj = jsrt::this()->toStr()->value;
            $len = strlen($obj);
            $start = $start->toInteger()->value;
            $end = ($end==jsrt::$undefined)?$len:$end->toInteger()->value;
            $start = ($start<0)?max($len+$start,0):min($start,$len);
            $end = ($end<0)?max($len+$end, 0):min($end, $len);
            $len = max($end-$start, 0);
            $str = substr($obj, $start, $len);
            return jss::js_str($str);
        }
        static public function split($sep, $limit) {
            $obj = jsrt::this()->toStr()->value;
            $limit = ($limit==jsrt::$undefined)?0xffffffff:$limit->toUInt32()->value;
            if (js::get_classname_without_namespace($sep)=="js_regexp") {
                // XXX finish me once RegExp is there
                throw new js_exception(new js_error("string::split(//) not implemented"));
            }
            $sep = $sep->toStr()->value;
            $array = explode($sep, $obj);
            return js_array(count($array), $array);
        }
        static public function substr($start, $length) {
            $obj = jsrt::this()->toStr()->value;
            $len = strlen($obj);
            $start = $start->toInteger()->value;
            $length = ($length==jsrt::$undefined)?1e80:$length->toInteger()->value;
            $start = ($start>=0)?$start:max($len+$start,0);
            $length = min(max($length,0), $len-$start);
            if ($length<=0) return jss::js_str("");
            return jss::js_str(substr($obj, $start, $length));
        }
        static public function substring($start, $end) {
            $obj = jsrt::this()->toStr()->value;
            $len = strlen($obj);
            $start = $start->toInteger()->value;
            $end = ($end==jsrt::$undefined)?$len:$end->toInteger()->value;
            $start = min(max($start,0),$len);
            $end = min(max($end,0),$len);
            $len = max($start,$end) - min($start,$end);
            return jss::js_str(substr($obj, $start, $len));
        }
        static public function toLowerCase() {
            return jss::js_str(strtolower(jsrt::this()->toStr()->value));
        }
        static public function toLocaleLowerCase() {
            // the i18n force is not strong with this one.
            return js_string::toLowerCase();
        }
        static public function toUpperCase() {
            return jss::js_str(strtoupper(jsrt::this()->toStr()->value));
        }
        static public function ToLocaleUpperCase() {
            return js_string::toUpperCase();
        }
    }
    class js_boolean extends js_object {
        function __construct($value = NULL) {
            parent::__construct("Boolean", jsrt::$proto_boolean);
            if ($value==NULL) $value = jsrt::$undefined;
            $this->value = $value->toBoolean();
        }
        function defaultValue($iggy=NULL) {
            return $this->value;
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static public function object($value) {
            if (js_function::isConstructor()) {
                return new js_boolean($value);
            } else {
                return $value->toBoolean();
            }
        }
        static public function toString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_boolean") throw new js_exception(new js_typeerror());
            return $obj->value->value==jsrt::$true?jss::js_str("true"):jss::js_str("false");
        }
        static public function valueOf() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_boolean") throw new js_exception(new js_typeerror());
            return $obj->value;
        }

    }
    class js_number extends js_object {
        function __construct($value = NULL) {
            parent::__construct("Number", jsrt::$proto_number);
            if ($value==NULL) $value = jsrt::$zero;
            $this->value = $value->toNumber();
        }
        function defaultValue($iggy=NULL) {
            return $this->value;
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static public function object($value) {
            if (js_function::isConstructor()) {
                return new js_number($value);
            } else {
                return $value->toNumber();
            }
        }
        static public function toString() {
            list($radix) = func_get_args();
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_number") throw new js_exception(new js_typeerror());
            $x = $obj->toNumber()->value;

            if (is_nan($x)) return jss::js_str("NaN");
            if ($x == 0) return jss::js_str("0");
            if ($x < 0 and is_infinite($x)) return jss::js_str("-Infinity");
            if (is_infinite($x)) return jss::js_str("Infinity");

            $radix = ($radix == jsrt::$undefined)?10:$radix->toNumber()->value;
            if ($radix<2 || $radix>36) $radix=10;
            $v = base_convert($x, 10, $radix);
            if ($x<0 and $v[0]!='-') $v = "-".$v;
            return jss::js_str($v);
        }
        static public function valueOf() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_number") throw new js_exception(new js_typeerror());
            return $obj->toNumber()->value;  
        }
        static public function toFixed($digits) {
            $obj = jsrt::this();
            $f = $digits->toInteger()->value;
            if ($f<0 || $f>20) throw js_exception(js_rangeerror());
            $x = $obj->toNumber()->value;
            if (is_nan($x)) return jss::js_str("NaN");
            if (is_infinite($x)) return js_number::toString();
            //return jss::js_str(number_format($x, $f));
            // el cheapo version
            $s = strval($x);
            if (strpos($s,".")===false) {
                return jss::js_str($s.".".str_repeat("0",$digits));
            }
            $k = explode(".",$s);
            if ($f>strlen($k[1])) {
                return jss::js_str($k[0].".".$k[1].str_repeat("0",$f-strlen($k[1])));
            } else {
                return jss::js_str($k[0].".".substr($k[1],0,$f));
            }
        }
        static public function toExponential($digits) {
            $obj = jsrt::this();
            $f = $digits->toInteger()->value;
            if ($f<0 || $f>20) throw js_exception(js_rangeerror());
            $x = $obj->toNumber()->value;
            if (is_nan($x)) return jss::js_str("NaN");
            if (is_infinite($x)) return js_number::toString();
            return jss::js_str(sprintf("%.".(1+$f)."e", $x));
        }
        static public function toPrecision($digits) {
            $obj = jsrt::this();
            if ($digits == jsrt::$undefined) return js_number::toString($digits);
            $f = $digits->toInteger()->value;
            if ($f<1 || $f>21) throw js_exception(js_rangeerror());
            $x = $obj->toNumber()->value;
            if (is_nan($x)) return jss::js_str("NaN");
            if (is_infinite($x)) return js_number::toString();
            if ($x>("1e$f"-0) || $x<1e-6) return jss::js_str(sprintf("%.".$f."e", $x));
            // not correct. we should count the total number of digits, but yeah, blah.
            return js_number::toFixed($digits);
        }
    }
    class js_math extends js_object {
        function __construct() {
            parent::__construct("Math");
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function abs($x) {
            return jss::js_int( abs($x->toNumber()->value));
        }
        static function acos($x) {
            return jss::js_int( acos($x->toNumber()->value));
        }
        static function asin($x) {
            return jss::js_int( asin($x->toNumber()->value));
        }
        static function atan($x) {
            return jss::js_int( atan($x->toNumber()->value));
        }
        static function atan2($y, $x) {
            return jss::js_int( atan2($y->toNumber()->value, $x->toNumber()->value));
        }
        static function ceil($x) {
            return jss::js_int( ceil($x->toNumber()->value));
        }
        static function cos($x) {
            return jss::js_int( cos($x->toNumber()->value));
        }
        static function exp($x) {
            return jss::js_int( exp($x->toNumber()->value));
        }
        static function floor($x) {
            return jss::js_int( floor($x->toNumber()->value));
        }
        static function log($x) {
            return jss::js_int( log($x->toNumber()->value));
        }
        static function max($v1, $v2) {
            $args = func_get_args();
            if (count($args)==0) return jss::js_int(log(0)); //-Infinity
            $arr = array();
            foreach ($args as $arg) {
                $v = $arg->toNumber()->value;
                if (is_nan($v)) return jsrt::$nan;
                $arr[] = $v;
            }
            return jss::js_int( max($arr));
        }
        static function min($v1, $v2) {
            $args = func_get_args();
            if (count($args)==0) return jsrt::$infinity;
            $arr = array();
            foreach ($args as $arg) {
                $v = $arg->toNumber()->value;
                if (is_nan($v)) return jsrt::$nan;
                $arr[] = $v;
            }
            return jss::js_int( min($arr));
        }
        static function pow($x,$y) {
            return jss::js_int (pow($x->toNumber()->value, $y->toNumber()->value));
        }
        static function random() {
            return jss::js_int( mt_rand()/mt_getrandmax() );
        }
        static function round($x) {
            return jss::js_int( round($x->toNumber()->value));
        }
        static function sin($x) {
            return jss::js_int( sin($x->toNumber()->value));
        }
        static function sqrt($x) {
            return jss::js_int( sqrt($x->toNumber()->value));
        }
        static function tan($x) {
            return jss::js_int( tan($x->toNumber()->value));
        }
    }
    class js_date extends js_object {
        function __construct($y=NULL,$m=NULL,$d=NULL,$h=NULL,$mn=NULL,$s=NULL,$ms=NULL) {
            parent::__construct("Date", jsrt::$proto_date);
            $y=($y==NULL)?jsrt::$undefined:$y;
            $m=($m==NULL)?jsrt::$undefined:$m;
            $d=($d==NULL)?jsrt::$undefined:$d;
            $h=($h==NULL)?jsrt::$undefined:$h;
            $mn=($mn==NULL)?jsrt::$undefined:$mn;
            $s=($s==NULL)?jsrt::$undefined:$s;
            $ms=($ms==NULL)?jsrt::$undefined:$ms;
            if ($y==jsrt::$undefined) {
                $value = floor(microtime(true)*1000);
            } elseif ($m==jsrt::$undefined) {
                $v = $y->toPrimitive();
                if ($v->type==js_val::STRING) {
                    $value = strtotime($v->value)*1000;
                } else {
                    $value = $v->toNumber()->value;
                }
            } else {
                $y = $y->toNumber()->value;
                $m = $m->toNumber()->value;
                $d = ($d==jsrt::$undefined)?1:$d->toNumber()->value;
                $h = ($h==jsrt::$undefined)?0:$h->toNumber()->value;
                $mn=($mn==jsrt::$undefined)?0:$mn->toNumber()->value;
                $s = ($s==jsrt::$undefined)?0:$s->toNumber()->value;
                $ms=($ms==jsrt::$undefined)?0:$ms->toNumber()->value;
                if (!is_nan($y)) {
                    $y2k = floor($y);
                    if ($y2k>=0 and $y2k<=99) $y = 1900+$y2k;
                }
                $value = 1000*mktime($h, $mn, $s, $m+1, $d, $y)+$ms;
            }
            $this->value = $value;
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($value) {
            list($y, $m, $d, $h, $m, $s, $ms) = func_get_args();
            if (js_function::isConstructor()) {
                return new js_date($y,$m,$d,$h,$m,$s,$ms);
            } else {
                $d = new js_date($y,$m,$d,$h,$m,$s,$ms);
                return $d->toStr();
            }
        }
        static function parse($v) {
            return jss::js_int(strtotime($v->toStr()->value)*1000);
        }
        static function UTC($y,$m,$d,$h,$mn,$s,$ms) {
            $y = $y->toNumber()->value;
            $m = $m->toNumber()->value;
            $d = ($d==jsrt::$undefined)?1:$d->toNumber()->value;
            $h = ($h==jsrt::$undefined)?0:$h->toNumber()->value;
            $mn=($mn==jsrt::$undefined)?0:$mn->toNumber()->value;
            $s = ($s==jsrt::$undefined)?0:$s->toNumber()->value;
            $ms=($ms==jsrt::$undefined)?0:$ms->toNumber()->value;
            if (!is_nan($y)) {
                $y2k = floor($y);
                if ($y2k>=0 and $y2k<=99) $y = 1900+$y2k;
            }
            $value = 1000*gmmktime($h, $mn, $s, $m+1, $d, $y)+$ms;
            return jss::js_int($value);
        }
        static function toString() {
            // Gecko: Sat Jun 25 2005 02:55:46 GMT -0700 (Pacific Daylight Time)
            // MSIE: Sat Jun 25 02:56:25 PDT 2005
            // let's go with RFC 2822
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            return jss::js_str(date("r", $obj->value/1000));
        }
        static function toDateString() {
            // Gecko: Sat Jun 25 2005
            // MSIE: Sat Jun 25 2005
            // they agree. weird.
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            return jss::js_str(date("D M j Y", $obj->value/1000));
        }
        static function toTimeString() {
            // Gecko: 03:13:37 GMT -0700 (Pacific Daylight Time)
            // MSIE: 03:14:00 PDT
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            return jss::js_str(date("G:i:s T", $obj->value/1000));
        }
        static function toLocaleString() {
            // Gecko: Saturday, June 25, 2005 03:15:55
            // MSIE: Saturday, June 25, 2005 03:16:21 AM
            // Us: Whatever PHP wants to do.
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            return jss::js_str(strftime("%c", $obj->value/1000));
        }
        static function toLocaleDateString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            return jss::js_str(strftime("%x", $obj->value/1000));
        }
        static function toLocaleTimeString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            return jss::js_str(strftime("%X", $obj->value/1000));
        }
        static function valueOf() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            return jss::js_int($obj->value);
        }
        static function getTime() {
            return js_date::valueOf();
        }
        static function getFullYear() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(date("Y", $t/1000));
        }
        static function getUTCFullYear() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(gmdate("Y", $t/1000));
        }
        static function getMonth() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(date("n", $t/1000)-1);
        }
        static function getUTCMonth() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(gmdate("n", $t/1000)-1);
        }
        static function getDate() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(date("j", $t/1000));
        }
        static function getUTCDate() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(gmdate("j", $t/1000));
        }
        static function getDay() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(date("w", $t/1000));
        }
        static function getUTCDay() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(gmdate("w", $t/1000));
        }
        static function getHours() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(date("G", $t/1000));
        }
        static function getUTCHours() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(gmdate("G", $t/1000));
        }
        static function getMinutes() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(date("i", $t/1000));
        }
        static function getUTCMinutes() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(gmdate("i", $t/1000));
        }
        static function getSeconds() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(date("s", $t/1000));
        }
        static function getUTCSeconds() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int(gmdate("s", $t/1000));
        }
        static function getMillieconds() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int($t%1000);
        }
        static function getUTCMilliseconds() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            return jss::js_int($t%1000);
        }
        static function getTimezoneOffset() {
            $t = js_date::valueOf()->value;
            if (is_nan($t)) return jsrt::$nan;
            $s = gettimeofday();
            return jss::js_int($t["minuteswest"]);
        }
        static function setTime($time) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $v = $time->toNumber()->value;
            $obj->value = $v;
            return jss::js_int($v);
        }
        static function setMilliseconds($ms) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = js_date::valueOf()->value;
            $ms = $ms->toNumber()->value;
            $v = floor($t/1000)*1000 + $ms;
            $obj->value = $v;
            return $v;
        }
        static function setUTCMilliseconds($ms) {
            return js_date::setMilliseconds($ms);
        }
        static function setSeconds($s, $ms) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $s = $s->toNumber()->value;
            $ms = ($ms == jsrt::$undefined)?($t%1000):$ms->toNumber()->value;
            $v = floor($t/60000)*60000 + ( 1000*$s + $ms );
            $obj->value = $v;
            return $v;
        }
        static function setUTCSeconds($s, $ms) {
            return js_date::setSeconds($s, $ms);
        }
        static function setMinutes($min, $sec, $ms) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $min = $min->toNumber()->value;
            $sec = ($sec==jsrt::$undefined)?js_date::getSeconds():$sec->toNumber()->value;
            $ms = ($ms == jsrt::$undefined)?($t%1000):$ms->toNumber()->value;
            $v = mktime(js_date::getHours(), $min, $sec, js_date::getMonth(),
            js_date::getDate(), js_date::getYear())*1000 + $ms;
            $obj->value = $v;
            return $v;
        }
        static function setUTCMinutes($min, $sec, $ms) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $min = $min->toNumber()->value;
            $sec = ($sec==jsrt::$undefined)?js_date::getUTCSeconds():$sec->toNumber()->value;
            $ms = ($ms == jsrt::$undefined)?($t%1000):$ms->toNumber()->value;
            $v = gmmktime(js_date::getUTCHours(), $min, $sec, js_date::getUTCMonth(),
            js_date::getUTCDate(), js_date::getUTCYear())*1000 + $ms;
            $obj->value = $v;
            return $v;
        }
        static function setHours($hour, $min, $sec, $ms) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $hour = $hour->toNumber()->value;
            $min = ($min==jsrt::$undefined)?js_date::getMinutes():$min->toNumber()->value;
            $sec = ($sec==jsrt::$undefined)?js_date::getSeconds():$sec->toNumber()->value;
            $ms = ($ms == jsrt::$undefined)?($t%1000):$ms->toNumber()->value;
            $v = mktime($hour, $min, $sec, js_date::getMonth(), 
            js_date::getDate(), js_date::getYear())*1000 + $ms;
            $obj->value = $v;
            return $v;
        }
        static function setUTCHours($hour, $min, $sec, $ms) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $hour = $hour->toNumber()->value;
            $min = ($min==jsrt::$undefined)?js_date::getUTCMinutes():$min->toNumber()->value;
            $sec = ($sec==jsrt::$undefined)?js_date::getUTCSeconds():$sec->toNumber()->value;
            $ms = ($ms == jsrt::$undefined)?($t%1000):$ms->toNumber()->value;
            $v = gmmktime($hour, $min, $sec, js_date::getUTCMonth(), 
            js_date::getUTCDate(), js_date::getUTCYear())*1000 + $ms;
            $obj->value = $v;
            return $v;
        }
        static function setDate($date) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $date = $date->toNumber()->value;
            $v = mktime(js_date::getHours(), js_date::getMinutes(), js_date::getSeconds(),
            js_date::getMonth(), $date, js_date::getYear())*1000 + ($t%1000);
            $obj->value = $v;
            return $v;
        }
        static function setUTCDate($date) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $date = $date->toNumber()->value;
            $v = gmmktime(js_date::getUTCHours(), js_date::getUTCMinutes(), js_date::getUTCSeconds(),
            js_date::getUTCMonth(), $date, js_date::getUTCYear())*1000 + ($t%1000);
            $obj->value = $v;
            return $v;
        }
        static function setMonth($month, $date) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $month = $month->toNumber()->value;
            $date=($date==jsrt::$undefined)?js_date::getDate():$date->toNumber()->value;
            $v = mktime(js_date::getHours(), js_date::getMinutes(), js_date::getSeconds(),
            $month, $date, js_date::getYear())*1000 + ($t%1000);
            $obj->value = $v;
            return $v;
        }
        static function setUTCMonth($month, $date) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $month = $month->toNumber()->value;
            $date=($date==jsrt::$undefined)?js_date::getUTCDate():$date->toNumber()->value;
            $v = gmmktime(js_date::getUTCHours(), js_date::getUTCMinutes(), js_date::getUTCSeconds(),
            $month, $date, js_date::getUTCYear())*1000 + ($t%1000);
            $obj->value = $v;
            return $v;
        }
        static function setFullYear($year, $month, $date) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $year = $year->toNumber()->value;
            $month = ($month==jsrt::$undefined)?js_date::getMonth():$month->toNumber()->value;
            $date = ($date==jsrt::$undefined)?js_date::getMinutes():$date->toNumber()->value;
            $v = mktime(js_date::getHours(), js_date::getDate(), js_date::getSeconds(),
            $month, $date, $year)*1000 + ($t%1000);
            $obj->value = $v;
            return $v;
        }
        static function setUTCFullYear($year, $month, $date) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            $year = $year->toNumber()->value;
            $month = ($month==jsrt::$undefined)?js_date::getUTCMonth():$month->toNumber()->value;
            $date = ($date==jsrt::$undefined)?js_date::getUTCDate():$date->toNumber()->value;
            $v = gmmktime(js_date::getUTCHours(), js_date::getUTCMinutes(), js_date::getUTCSeconds(),
            $month, $date, $year)*1000 + ($t%1000);
            $obj->value = $v;
            return $v;
        }
        static function toUTCString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_date") throw new js_exception(new js_typeerror());
            $t = $obj->value;
            return jss::js_str(gmstrftime("%c", $t/1000));
        }

    }
    class js_regexp extends js_object {
        public $pattern;
        public $flags;
        function __construct($pattern=NULL, $flags=NULL) {
            parent::__construct("RegExp", jsrt::$proto_regexp);
            $this->pattern = $pattern;
            $this->flags = $flags;
            $this->put("global", (strchr($flags, "g")!==FALSE)?jsrt::$true:jsrt::$false, array("dontdelete","readonly","dontenum"));
            $this->put("ignoreCase", (strchr($flags, "i")!==FALSE)?jsrt::$true:jsrt::$false, array("dontdelete","readonly","dontenum"));
            $this->put("multiline", (strchr($flags, "m")!==FALSE)?jsrt::$true:jsrt::$false, array("dontdelete","readonly","dontenum"));
            $this->put("source", jss::js_str($pattern), array("dontdelete","readonly","dontenum"));
            $this->put("lastIndex", jsrt::$zero, array("dontdelete", "dontenum"));
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($value) {
            list ($pattern, $flags) = func_get_args();
            if (!js_function::isConstructor() and js::get_classname_without_namespace($pattern)=="js_regexp" and $flags==jsrt::$undefined) {
                return $pattern;
            }
            if (js::get_classname_without_namespace($pattern)=="js_regexp") {
                if ($flags!=jsrt::$undefined) {
                    throw new js_exception(new js_typeerror());
                }
                $flags = $pattern->flags;
                $pattern = $pattern->pattern;
            } else {
                $flags = ($flags == jsrt::$undefined)?"":$flags->toStr()->value;
                $pattern = ($pattern == jsrt::$undefined)?"":$pattern->toStr()->value;
            }
            return new js_regexp($pattern, $flags);
        }
        static function exec($str) {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_regexp") throw new js_exception(new js_typeerror());
            $s = $str->toStr()->value;
            $len = strlen($s);
            $lastIndex = $obj->get("lastIndex")->toInteger()->value;
            $i=$lastIndex;
            if ($obj->get("global")->toBoolean()->value== false) $i=0;
            do {
                if ($i<0 or $i>$len) {
                    $obj->put("lastIndex", jsrt::$zero);
                    return jsrt::$null;
                }
                $r = $obj->match($s, $i); // XXX write js_regexp::match()
                $i++;
            } while ($r == NULL);
            $e = $r["endIndex"];
            $n = $r["length"];
            if ($obj->get("global")->toBoolean()->value==true) {
                $obj->put("lastIndex", jss::js_int($e));
            }
            $array = new js_array();
            $array->put("index", jss::js_int($i-1));
            $array->put("input", $str);
            $array->put("length", $n+1);
            $array->put(0, jss::js_str(substr($s, $i-1, $e-$i)));
            for($i=0;$i<$n;$i++) {
                $array->put($i+1, jss::js_str($r[$i]));
            }
            return $array;
        }
        static function test($str) {
            return (js_regexp::exec($str)!=NULL)?jsrt::$true:jsrt::$false;
        }
        static function toString() {
            $obj = jsrt::this();
            if (js::get_classname_without_namespace($obj)!="js_regexp") throw new js_exception(new js_typeerror());
            $s = "/".str_replace(array("/","\\"),array("\/","\\\\"),$obj->pattern)."/";
            if ($obj->get("global")==jsrt::$true) $s.="g";
            if ($obj->get("ignoreCase")==jsrt::$true) $s.="i";
            if ($obj->get("multiline")==jsrt::$true) $s.="m";
            return jss::js_str($s);
        }

    }
    class js_error extends js_object {
        function __construct($class="Error", $proto=NULL, $msg='') {
            parent::__construct($class, ($proto==NULL)?jsrt::$proto_error:$proto);
            $this->put("name", jss::js_str($class));
            $this->put("message", jss::js_str($msg));
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($message) {
            return new js_error("Error", NULL, $message->toStr()->value);
        }
        static function toString() {
            $obj = jsrt::this();
            if (!($obj instanceof js_error)) throw new js_exception(new js_typeeror());
            return jss::js_str(js::get_classname_without_namespace($obj).": ".$obj->get("message")->toStr()->value);
        }
    }
    class js_evalerror extends js_error {
        function __construct($msg = '') {
            parent::__construct("EvalError", jsrt::$proto_evalerror, $msg);
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($message) {
            return new self($message->toStr()->value);
        }
    }
    class js_rangeerror extends js_error {
        function __construct($msg = '') {
            parent::__construct("RangeError", jsrt::$proto_rangeerror, $msg);
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($message) {
            return new self($message->toStr()->value);
        }
    }
    class js_referenceerror extends js_error {
        function __construct($msg = '') {
            parent::__construct("ReferenceError", jsrt::$proto_referenceerror, $msg);
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($message) {
            return new self($message->toStr()->value);
        }
    }
    class js_syntaxerror extends js_error {
        function __construct($msg = '') {
            parent::__construct("SyntaxError", jsrt::$proto_syntaxerror, $msg);
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($message) {
            return new self($message->toStr()->value);
        }
    }
    class js_typeerror extends js_error {
        function __construct($msg = '') {
            parent::__construct("TypeError", jsrt::$proto_typeerror, $msg);
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($message) {
            return new self($message->toStr()->value);
        }
    }
    class js_urierror extends js_error {
        function __construct($msg = '') {
            parent::__construct("URIError", jsrt::$proto_urierror, $msg);
        }
        ////////////////////////
        // scriptable methods //
        ////////////////////////
        static function object($message) {
            return new self($message->toStr()->value);
        }
    }

    class js_ref {
        public $type;
        public $base;
        public $propName;
        function __construct($base, $propName) {
            $this->type = js_val::REF;
            $this->base = $base;
            $this->propName = $propName;
        }
        function getValue() {
            if (!is_object($this->base)) {
                echo "<pre>";
                debug_print_backtrace();
                echo "</pre>";
            }
            return $this->base->get($this->propName);
        }
        function putValue($w, $ret=0) {
            $v = null;
            if ($ret==2) { 
                $v = $this->base->get($this->propName);
            }
            $this->base->put($this->propName, $w);
            if ($ret==1) return $w;
            return $v;
        }
    }
    class js_ref_null extends js_ref {
        function __construct($propName) {
            parent::__construct(NULL, $propName);
        }
        function getValue() {
            echo "oops. trying to read ".$this->propName.", but that's not defined.<hr>";
            throw new js_exception(new js_referenceerror(dump_object($this)));
        }
        function putValue($w, $ret=0) {
            jsrt::$global->put($this->propName, $w);
        }
    }

    ///////////////////////////////////////////
    //  
    ///////////////////////////////////////////

    function dump_object($o) {
        $s = '['.$o->type.']-';
        if ($o instanceof js_ref) {
            $s.="(".$o->base.".".$o->propName.")";
        } else {
            $s.="{";
                foreach ($o->slots as $index=>$value) {
                    $s.="'$index': ".$value->value.",\n";
                }
                $s.="}";
            }
            return $s;
        }

        function jsi_empty() {
            return jsrt::$undefined;
        }
        function jsi_eval() {
            throw new js_exception(new js_syntaxerror("Eval is not implemented"));
        }
        function jsi_parseInt($str, $radix) {
            $radix = $radix->toNumber()->value;
            if ($radix==0) $radix=10;
            return jss::js_int(intval($str->toStr()->value, $radix));
        }
        function jsi_parseFloat($str) {
            return jss::js_int(floatval($str->toStr()->value));
        }
        function jsi_isNaN($val) {
            return is_nan($val->toNumber()->$value)?jsrt::$true:jsrt::$false;
        }
        function jsi_isFinite($val) {
            return is_finite($val->toNumber()->$value)?jsrt::$true:jsrt::$false;
        }
        function jsi_decodeURI($uri) {
            throw new js_error("decodeURI not implemented");
        }
        function jsi_decodeURIComponent($uri) {
            throw new js_error("decodeURIComponent not implemented");
        }
        function jsi_encodeURI($uri) {
            throw new js_error("encodeURI not implemented");
        }
        function jsi_encodeURIComponent($uri) {
            throw new js_error("encodeURIComponent not implemented");
        }

