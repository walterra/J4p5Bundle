<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

use \Walterra\J4p5Bundle\j4p5\parse\token_source;
use \Walterra\J4p5Bundle\j4p5\parse\stream;
use \Walterra\J4p5Bundle\j4p5\parse\token;
use \Walterra\J4p5Bundle\j4p5\jsly;

class preg_scanner extends token_source {
    function report_instant_description() {
        echo "Scanner State: $this->state<br/>\n";
    }
    function __construct($init_context, $p = NULL) {
        Helpers::bug_unless(func_num_args());
        $this->pattern = $p?$p:array('INITIAL'=>array());
        $this->state = 'INITIAL';
        $this->init_context = $init_context;
        $this->context = $init_context;
    }
    function add_state($name, $cluster) {
        Helpers::bug_unless(is_array($cluster));
        $this->pattern[$name] = $cluster;
    }
    function start($string) {
        $this->context = $this->init_context;
        $this->stream = new stream($string);
        $this->megaregexp = array();
        foreach ($this->pattern as $key=>$blah) {
            $s='';
            foreach ($this->pattern[$key] as $pattern) {
                if ($s) $s.='|';
                $s .= $pattern[0];
            }
            $s = '('.$s.')';
            $this->megaregexp[$key] = $s;
        }
    }
    function next() {
        $start = $this->stream->pos();
        Helpers::bug_unless(is_array($this->pattern[$this->state]), 'No state called '.$this->state);
        # much faster implementation of the lexer, by leveraging PCRE a bit better.
        if ($match = $this->stream->test($this->megaregexp[$this->state])) {
            $text = $match[0];
            $tmp = array_flip($match);
            $index = $tmp[$text] -1;
            $pattern = $this->pattern[$this->state][$index];
            $type = $pattern[1]; //->type;
            $action = $pattern[3]; //->action;
            if ($action) jsly::$action($type, $text, $match, $this->state, $this->context);
            if ($pattern[2]) return $this->next();
            $stop = $this->stream->pos();
            return new token($type, $text, $start, $stop);
        }
        return $this->stream->default_rule();
    }
}

?>