<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

use \Walterra\J4p5Bundle\j4p5\parse\Helpers;

class state_set_labeler {
    function state_set_labeler() {
        $this->map=array();
    }
    function label($list) {
        sort($list);
        $key = implode(':', $list);
        if (empty($this->map[$key])) $this->map[$key] = Helpers::gen_label();
        return $this->map[$key];
    }
}

?>