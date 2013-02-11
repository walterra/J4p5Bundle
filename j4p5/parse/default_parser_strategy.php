<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

use \Walterra\J4p5Bundle\j4p5\parse\Helpers;
use \Walterra\J4p5Bundle\j4p5\parse\parse_error;

class default_parser_strategy extends parser_strategy {
    function stuck($token, $lex, $stack) {
        Helpers::send_parse_error_css_styles();
        ?>
        <hr/>The LR parser is stuck. Source and grammar do not agree.<br/>
        Looking at token:
        <?php
        Helpers::span('term', $token->text, $token->type);
        echo ' [ '.$token->type.' ]';
        echo "<br/>\n";
        $lex->report_error();
        echo "<hr/>\n";
        echo "Backtrace Follows:<br/>\n";
        # pr($stack);
        while (count($stack)) {
            $tos = array_pop($stack);
            echo $tos->trace()."<br/>\n";
        }
        throw new parse_error("Can't tell what to do with ".$token->type.".");
    }
    function assert_done($token, $lex) {
        if ($token->type) $this->stuck($token, $lex, array());
    }
}
