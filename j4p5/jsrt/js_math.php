<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

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

?>