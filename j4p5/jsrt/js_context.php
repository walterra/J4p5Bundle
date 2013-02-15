<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

class js_context {
    public $js_this;
    public $scope_chain;
    public $var;

    function __construct($that, $scope_chain, $var) {
        $this->js_this = $that;
        $this->scope_chain = $scope_chain;
        $this->var = $var;
    }
}

?>