<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jss;
use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

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

?>