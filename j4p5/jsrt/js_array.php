<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

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

?>