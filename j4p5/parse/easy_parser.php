<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

use \Walterra\J4p5Bundle\j4p5\parse\parser;
use \Walterra\J4p5Bundle\j4p5\jsly;

class easy_parser extends parser {
    function __construct($pda, $strategy = null) {
        parent::__construct($pda);
        $this->call = $this->action; //array();
        $this->strategy = ($strategy ? $strategy : new default_parser_strategy());
        /*
        foreach($this->action as $k => $body) {
        $this->call[$k] = create_function( '$tokens', preg_replace('/{(\d+)}/', '$tokens[\\1]', $body));
        }
        */
    }
    function reduce($action, $tokens) {
        $call = $this->call[$action];
        return jsly::$call($tokens);
    }
    function parse($symbol, $lex, $strategy = null) {
        return parent::parse($symbol, $lex, $this->strategy);
    }
}

    
?>