<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

class js_attribute {
    public $value;
    public $readonly = false;
    public $dontenum = false;
    public $dontdelete = false;
    function __construct($value, $ro=0, $de=0, $dd=0) {
        $this->value = $value;
        $this->readonly = $ro;
        $this->dontenum = $de;
        $this->dontdelete = $dd;
    }
}

