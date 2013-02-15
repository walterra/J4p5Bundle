<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

class js_exception extends Exception {
    const EXCEPTION = 7;
    const NORMAL = 8;
    public $type;
    public $value;
    function __construct($value) {
        parent::__construct();
        $this->type = self::EXCEPTION;
        $this->value = $value;
    }
}

?>