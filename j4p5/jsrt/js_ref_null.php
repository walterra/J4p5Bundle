<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_exception;
use Walterra\J4p5Bundle\j4p5\jsrt\js_referenceerror;

class js_ref_null extends js_ref {
    function __construct($propName) {
        parent::__construct(NULL, $propName);
    }
    function getValue() {
        echo "oops. trying to read ".$this->propName.", but that's not defined.<hr>";
        throw new js_exception(new js_referenceerror(dump_object($this)));
    }
    function putValue($w, $ret=0) {
        jsrt::$global->put($this->propName, $w);
    }
}

?>