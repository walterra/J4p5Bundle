<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\js;
use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_exception;
use Walterra\J4p5Bundle\j4p5\jsrt\js_typeerror;

/* a bit heavy for every value. optimize later. XXX */
class js_val {
    const UNDEFINED = 0;
    const NULL = 1;
    const BOOLEAN = 2;
    const NUMBER = 3;
    const STRING = 4;
    const OBJECT = 5;
    const REF = 6;
    public $type;
    public $value;
    function __construct($type, $value) {
        list($this->type, $this->value) = func_get_args();
    }
    function toPrimitive($hint = NULL) {
        if ($this->type!=js_val::OBJECT) return $this;
        if ($hint!=NULL) {
            $v = $this->defaultValue($hint);
        } else {
            $v = $this->defaultValue();
        }
        return $v;
    }
    function toBoolean() {
        switch ($this->type) {
            case js_val::UNDEFINED:
            case js_val::NULL:
            return jsrt::$false;
            case js_val::OBJECT:
            return jsrt::$true;
            case js_val::BOOLEAN:
            return $this;
            case js_val::NUMBER:
            return ($this->value == 0 or is_nan($this->value))?jsrt::$false:jsrt::$true;
            case js_val::STRING:
            return (strlen($this->value)==0)?jsrt::$false:jsrt::$true;
        }
    }
    function toNumber() {
        switch ($this->type) {
            case js_val::UNDEFINED:
            return jsrt::$nan;
            case js_val::NULL:
            return jsrt::$zero;
            case js_val::BOOLEAN:
            return $this->value?jsrt::$one:jsrt::$zero;
            case js_val::NUMBER:
            return $this;
            case js_val::STRING:
            return is_numeric($this->value)?js_int((float)$this->value):jsrt::$nan;
            case js_val::OBJECT:
            return $this->toPrimitive(js_val::NUMBER)->toNumber();
        }
    }
    function toInteger() {
        $v = $this->toNumber();
        if (is_nan($v->value)) return jsrt::$zero;
        if ($v->value == 0 or is_infinite($v->value)) return $v;
        return js_int($v->value/abs($v->value)*floor(abs($v->value)));
    }
    function toInt32() {
        $v = $this->toInteger();
        if (is_infinite($v->value)) return jsrt::$zero;
        return js_int( (int)$v->value );
    }
    function toUInt32() {
        $v = $this->toInteger();
        if (is_infinite($v->value)) return jsrt::$zero;
        return js_int( bcmod($v->value , 4294967296 )); // should keep a float.
    }
    function toUInt16() {
        $v = $this->toInteger();
        if (is_infinite($v->value)) return jsrt::$zero;
        return js_int( $v->value % 0x10000 );
    }
    function toStr() {
        switch ($this->type) {
            case js_val::UNDEFINED: 
            return js_str("undefined");
            case js_val::NULL: 
            return js_str("null");
            case js_val::BOOLEAN: 
            return js_str($this->value?"true":"false");
            case js_val::STRING: 
            return $this;
            case js_val::OBJECT: 
            return $this->toPrimitive(js_val::STRING)->toStr();
            case js_val::NUMBER:
            if (is_nan($this->value)) return js_str("NaN");
            if ($this->value == 0) return js_str("0");
            if ($this->value < 0) { 
                $v = js_int(-$this->value)->toStr();
                return js_str("-".$v->value);
            }
            if (is_infinite($this->value)) return js_str("Infinity");
            return js_str( (string)$this->value);
        }
    }
    function toObject() {
        switch ($this->type) {
            case js_val::UNDEFINED:
            case js_val::NULL:
            throw new js_exception(new js_typeerror("Cannot convert null or undefined to objects"));
            /* XXX Throw a TypeError exception */
            return NULL;
            case js_val::BOOLEAN:
            return new js_boolean($this);
            case js_val::NUMBER:
            return new js_number($this);
            case js_val::STRING:
            return new js_string($this);
            case js_val::OBJECT:
            return $this;
        }
    }
    function toDebug() {
        switch($this->type) {
            case js_val::UNDEFINED: return "undefined";
            case js_val::NULL: return "null";
            case js_val::BOOLEAN: return $this->value?"true":"false";
            case js_val::NUMBER: return $this->value;
            case js_val::STRING: return var_export($this->value, 1);
            case js_val::OBJECT:
            $s = "class: ".js::get_classname_without_namespace($this)."<br>";
            foreach ($this->slots as $key=>$value) {
                $s .= "$key => ".$value->value."<br>";
            }
            return $s;
        }
    }
    function getValue() {
        // this should never get called, unless we have a logic bug.
        echo "##useless getValue##";flush();
        echo "<pre>";
        debug_print_backtrace();
        echo "</pre>";
        return $this;
    }
    function putValue($w) {
        throw new js_exception(new js_referenceerror(dump_object($v)));
    }
}

?>
