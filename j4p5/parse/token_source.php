<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

use \Walterra\J4p5Bundle\j4p5\parse\Helpers;

abstract class token_source {
    abstract function next();
    abstract function report_instant_description();
    function report_error() {
        $this->report_instant_description();
        echo "The next few tokens are:<br/>\n";
        for ($i=0; $i<15; $i++) {
            $tok = $this->next();
            Helpers::span('term', htmlSpecialChars($tok->text), $tok->type);
        }
    }
}

?>