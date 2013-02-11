<?php

namespace Walterra\J4p5Bundle\j4p5\jsc;

/*
This seems somehow a bit better than having monolithic sequential modules.
*/
abstract class js_construct {
    abstract function emit($w=0);
}

?>