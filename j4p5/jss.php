<?php

namespace Walterra\J4p5Bundle\j4p5;

use Exception;
use Iterator;


//////////////////////////////////////////////
// shortcuts to keep the generated code small
//////////////////////////////////////////////
class jss {
    static public function js_str($s) {
      static $cache = array();
      if (!isset($cache[$s])) {
        $cache[$s] = new js_val(js_val::STRING, $s);
      }
      return $cache[$s];
    }
    static public function js_int($i) {
      static $cache = array();
      $s = strval($i);
      if (!isset($cache[$s])) {
        $cache[$s] = new js_val(js_val::NUMBER, $i);
      }
      //echo "js_int($i) = ".serialize($cache[$s])."<br>";
      return $cache[$s];
    }
    static public function js_bool($v) {
      return $v->toBoolean()->value;
    }
    static public function js_obj($v) {
      return $v->toObject();
    }
    static public function js_thrown($v) {
      return (get_class($v)=="js_exception" and $v->type==js_exception::EXCEPTION);
    }

    static public function php_int($o) {
      return $o->toNumber()->value;
    }
    static public function php_str($o) {
      return $o->toStr()->value;
    }
}

?>
