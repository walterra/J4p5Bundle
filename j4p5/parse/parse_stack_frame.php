<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

class parse_stack_frame {
    private $symbol, $semantic;
    public $state;
    function __construct($symbol, $state) {
        $this->symbol = $symbol;
        $this->state = $state;
        $this->semantic = array();
    }
    function shift($semantic) { $this->semantic[] = $semantic; }
    function fold($semantic) { $this->semantic = array($semantic); }
    function semantic() { return $this->semantic; }
    function trace() { return "$this->symbol : $this->state"; }
}

