<?php

namespace Walterra\J4p5Bundle\j4p5;

use \Exception;
use Walterra\J4p5Bundle\j4p5\js;
use Walterra\J4p5Bundle\j4p5\jss;
use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_exception;
use Walterra\J4p5Bundle\j4p5\jsrt\js_val;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;
use Walterra\J4p5Bundle\j4p5\jsrt\js_function;
use Walterra\J4p5Bundle\j4p5\jsrt\js_context;
use Walterra\J4p5Bundle\j4p5\jsrt\js_array;
use Walterra\J4p5Bundle\j4p5\jsrt\js_string;
use Walterra\J4p5Bundle\j4p5\jsrt\js_boolean;
use Walterra\J4p5Bundle\j4p5\jsrt\js_number;
use Walterra\J4p5Bundle\j4p5\jsrt\js_math;
use Walterra\J4p5Bundle\j4p5\jsrt\js_date;
use Walterra\J4p5Bundle\j4p5\jsrt\js_regexp;
use Walterra\J4p5Bundle\j4p5\jsrt\js_error;
use Walterra\J4p5Bundle\j4p5\jsrt\js_evalerror;
use Walterra\J4p5Bundle\j4p5\jsrt\js_rangeerror;
use Walterra\J4p5Bundle\j4p5\jsrt\js_referenceerror;
use Walterra\J4p5Bundle\j4p5\jsrt\js_syntaxerror;
use Walterra\J4p5Bundle\j4p5\jsrt\js_typeerror;
use Walterra\J4p5Bundle\j4p5\jsrt\js_urierror;
use Walterra\J4p5Bundle\j4p5\jsrt\js_ref;
use Walterra\J4p5Bundle\j4p5\jsrt\js_ref_null;

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
        // echo "Computing ".get_class($obj->toObject())."->".$base."<br>";
        // jsrt::debug($obj);
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

