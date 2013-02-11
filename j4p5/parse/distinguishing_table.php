<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

class distinguishing_table {
    function distinguishing_table() {
        $this->dist = array();
    }
    function key($s1, $s2) {
        $them = array($s1, $s2);
        sort($them);
        return implode("|", $them);
    }
    function distinguish($s1, $s2) {
        $key = $this->key($s1, $s2);
        $this->dist[$key] = true;
    }
    function differ($s1, $s2) {
        $key = $this->key($s1, $s2);
        return isset($this->dist[$key]);
    }
}

?>