<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

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

?>