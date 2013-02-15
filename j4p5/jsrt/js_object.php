<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use \Iterator;
use Walterra\J4p5Bundle\j4p5\jsrt\js_val;
use Walterra\J4p5Bundle\j4p5\jsrt\js_attribute;
use Walterra\J4p5Bundle\j4p5\jsrt;

class js_object extends js_val implements Iterator {
    public $slots = array();
    public $prototype = NULL;
    public $class = "Object";

    function get($name) {
        $name = strval($name);
        if (isset($this->slots[$name])) {
            return $this->slots[$name]->value;
        } else {
            if ($this->prototype == NULL) return jsrt::$undefined;      
            return $this->prototype->get($name);
        }
    }
    function put($name, $value, $opts=NULL) {
        $name = strval($name);
        if (!$this->canPut($name)) return;
        if ($value instanceof js_ref) {
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
        }
        //$value = $value->getValue();
        if (isset($this->slots[$name])) {
            $o = $this->slots[$name];
            $o->value = $value;
        } else {
            $o = new js_attribute($value);
            $this->slots[$name] = $o;
        }
        if ($opts) {
            foreach ($opts as $opt) {
                $o->$opt = true;
            }
        }
    }
    function canPut($name) {
        $name = strval($name);
        if (isset($this->slots[$name])) {
            return $this->slots[$name]->readonly == false;
        }
        if ($this->prototype == NULL) return true;
        return $this->prototype->canPut($name);
    }
    function hasProperty($name) {
        if (isset($this->slots[strval($name)])) return true;
        if ($this->prototype == NULL) return false;
        return $this->prototype->hasProperty($name);
    }
    function delete($name) {
        $name = strval($name);
        if (!isset($this->slots[$name])) return true;
        if ($this->slots[$name]->dontdelete) return false;
        unset($this->slots[$name]);
        return true;
    }
    protected function pcall($n) {
        $p = $this->get($n);
        if ($p->type == js_val::OBJECT) {
            $v = $p->_call($this);
            if ($v->type != js_val::OBJECT) return $v;
        }
        return jsrt::$undefined;
    }
    function defaultValue($hint = js_val::NUMBER) {
        switch ($hint) {
            case js_val::STRING:
            $v = $this->pcall("toString");
            if ($v != jsrt::$undefined) return $v;
            $v = $this->pcall("valueOf");
            if ($v != jsrt::$undefined) return $v;
            break;
            case js_val::NUMBER:
            $v = $this->pcall("valueOf");
            if ($v != jsrt::$undefined) return $v;
            $v = $this->pcall("toString");
            if ($v != jsrt::$undefined) return $v;
            break;
        }
        // to a toSource(), just because.
        return $this->pcall("toSource");
    }
    function __construct($class="Object", $proto=NULL) {
        parent::__construct(js_val::OBJECT, NULL);
        switch ($class) {
            default: /* default to Object */
            case "Object":   $this->prototype = jsrt::$proto_object;   break;
            case "Function": $this->prototype = jsrt::$proto_function; break;
            case "Array":    $this->prototype = jsrt::$proto_array;    break;
            case "String":   $this->prototype = jsrt::$proto_string;   break;
            case "Boolean":  $this->prototype = jsrt::$proto_boolean;  break;
            case "Number":   $this->prototype = jsrt::$proto_number;   break;
            case "Date":     $this->prototype = jsrt::$proto_date;     break;
            case "RegExp":   $this->prototype = jsrt::$proto_regexp;   break;
            case "Error":    $this->prototype = jsrt::$proto_error;    break;
            case "EvalError":      $this->prototype = jsrt::$proto_evalerror;      $class="Error"; break;
            case "RangeError":     $this->prototype = jsrt::$proto_rangeerror;     $class="Error"; break;
            case "ReferenceError": $this->prototype = jsrt::$proto_referenceerror; $class="Error"; break;
            case "SyntaxError":    $this->prototype = jsrt::$proto_syntaxerror;    $class="Error"; break;
            case "TypeError":      $this->prototype = jsrt::$proto_typeerror;      $class="Error"; break;
            case "URIError":       $this->prototype = jsrt::$proto_urierror;       $class="Error"; break;
        }
        $this->class = $class;
        $this->prototype = ($proto==NULL)?jsrt::$proto_object:$proto;
    }
    //////////////////////
    // Iterator interface
    //////////////////////
    public function rewind() {
        reset($this->slots);
    }
    public function current() {
        $attr = current($this->slots);
        return $attr?key($this->slots):jsrt::$undefined;
    }
    public function key() {
        return key($this->slots);
    }
    public function next() {
        do {
            $attr = next($this->slots);
        } while ($attr and $attr->dontenum);
        return $attr?key($this->slots):jsrt::$undefined;
    }
    public function valid() {
        return (key($this->slots) !== NULL);
    }
    ////////////////////////
    // scriptable methods //
    ////////////////////////
    static public function object($value) {
        if ($value!=jsrt::$null and $value!=jsrt::$undefined) {
            return $value->toObject();
        }
        #-- back to our regularly scheduled constructor.
            return new js_object("Object");
    }
    static public function toString() {
        $obj = jsrt::this();
        return js_str("[object ".$obj->class."]");
    }
    static public function valueOf() {
        return jsrt::this();
    }
    static public function hasOwnProperty($value) {
        $obj = jsrt::this();
        $name = $value->toStr()->value;
        return (isset($obj->slots[$name]))?jsrt::$true:jsrt::$false;
    }
    static public function isPrototypeOf($value) {
        $obj = jsrt::this();
        if ($value->type != js_val::OBJECT) return jsrt::$false;
        do {
            $value = $value->prototype;
            if ($value == NULL) return jsrt::$false;
            if ($obj === $value) return jsrt::$true;
        } while (true);
    }
    static public function propertyIsEnumerable($value) {
        $obj = jsrt::this();
        $name = $value->toStr()->value;
        if (!isset($obj->slots[$name])) return jsrt::$false;
        $attr = $obj->slots[$name];
        return !$attr->dontenum;
    }
}

