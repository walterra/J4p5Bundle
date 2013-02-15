<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_error;

class js_referenceerror extends js_error {
    function __construct($msg = '') {
        parent::__construct("ReferenceError", jsrt::$proto_referenceerror, $msg);
    }
    ////////////////////////
    // scriptable methods //
    ////////////////////////
    static function object($message) {
        return new self($message->toStr()->value);
    }
}

?>