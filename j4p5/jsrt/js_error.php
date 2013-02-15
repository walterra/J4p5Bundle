<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jss;
use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

class js_error extends js_object {
    function __construct($class="Error", $proto=NULL, $msg='') {
        parent::__construct($class, ($proto==NULL)?jsrt::$proto_error:$proto);
        $this->put("name", jss::js_str($class));
        $this->put("message", jss::js_str($msg));
    }
    ////////////////////////
    // scriptable methods //
    ////////////////////////
    static function object($message) {
        return new js_error("Error", NULL, $message->toStr()->value);
    }
    static function toString() {
        $obj = jsrt::this();
        if (!($obj instanceof js_error)) throw new js_exception(new js_typeeror());
        return jss::js_str(js::get_classname_without_namespace($obj).": ".$obj->get("message")->toStr()->value);
    }
}

?>