<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt\js_val;

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

?>