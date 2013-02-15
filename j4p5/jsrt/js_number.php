<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

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

?>