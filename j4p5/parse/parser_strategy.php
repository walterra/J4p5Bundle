<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

abstract class parser_strategy {
    abstract function stuck($token, $lex, $stack);
    abstract function assert_done($token, $lex);
}

?>
