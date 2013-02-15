<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jss;
use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

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

?>