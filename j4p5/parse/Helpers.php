<?php

namespace Walterra\J4p5Bundle\j4p5\parse;

use \Walterra\J4p5Bundle\j4p5\parse\Bug;
use \Walterra\J4p5Bundle\j4p5\parse\Helpers;
use \Walterra\J4p5Bundle\j4p5\parse\token;

class Helpers {
    public static $FA_NO_MARK = 99999;

    static public function bug_unless($assertion, $gripe='Bug found.') { if (!$assertion) throw new Bug($gripe); }

    static public function span($class, $text, $title='') {
        $title = htmlspecialchars($title);
        if ($title) $extra = " title=\"$title\"";
        else $extra = '';
        echo "<span class=\"$class\"{$extra}>".$text."</span>\n";
    }
    
    static public function gen_label() {
        # Won't return the same number twice. Note that we use state labels
        # for hash keys all over the place. To prevent PHP from doing the
        # wrong thing when we merge such hashes, we tack a letter on the
        # front of the labels.
        static $count = 0;
        $count ++;
        return 's'.$count;
    }

    /*
    Note that you should really throw away any NFA once you have
    used one of the below functions on it, because the result will contain
    indentically named state labels from the originals. We could fix this
    apparent problem, but it would mean establishing a state-renaming function
    for NFAs. Because I don't care to do this just now, and it's not important
    anyway, I'm not doing it.
    */
    static public function nfa_union($nfa_list) {
        $out = new enfa();
        foreach($nfa_list as $nfa) {
            $out->copy_in($nfa);
            $out->add_epsilon($out->initial, $nfa->initial);
            $out->add_epsilon($nfa->final, $out->final);
        }
        return $out;
    }

    static public function nfa_concat($nfa_list) {
        $out = new enfa();
        $last_state = $out->initial;
        foreach($nfa_list as $nfa) {
            $out->copy_in($nfa);
            $out->add_epsilon($last_state, $nfa->initial);
            $last_state = $nfa->final;
        }
        $out->add_epsilon($last_state, $out->final);
        return $out;
    }
    
    public static function null_token() { return new token('','','',''); }

    public static function preg_pattern($regex, $type, $ignore, $action) {
        return array($regex, $type, $ignore, $action);
    }
    
    public static function preg_pattern_test($pattern, $string) {
        if (preg_match($pattern.'A', $string, $match)) return $match;
    }
    
    public static function send_parse_error_css_styles() {
        ?>
        <style>
            .term { border: 1px solid green; margin: 2px; }
        .char { border: 1px solid red; margin: 2px; }
        .nonterm { border: 1px solid blue; margin: 10px; }
        .wierd { border: 1px solid purple; margin: 2px; }
        pre { line-height: 1.5; }
        </style>
        <?php
    }
}

?>