<?php

namespace Walterra\J4p5Bundle\j4p5\jsrt;

use Walterra\J4p5Bundle\j4p5\jsrt;
use Walterra\J4p5Bundle\j4p5\jsrt\js_object;

class js_boolean extends js_object {
    function __construct($value = NULL) {
        parent::__construct("Boolean", jsrt::$proto_boolean);
        if ($value==NULL) $value = jsrt::$undefined;
        $this->value = $value->toBoolean();
    }
    function defaultValue($iggy=NULL) {
        return $this->value;
    }
    ////////////////////////
    // scriptable methods //
    ////////////////////////
    static public function object($value) {
        if (js_function::isConstructor()) {
            return new js_boolean($value);
        } else {
            return $value->toBoolean();
        }
    }
    static public function toString() {
        $obj = jsrt::this();
        if (js::get_classname_without_namespace($obj)!="js_boolean") throw new js_exception(new js_typeerror());
        return $obj->value->value==jsrt::$true?jss::js_str("true"):jss::js_str("false");
    }
    static public function valueOf() {
        $obj = jsrt::this();
        if (js::get_classname_without_namespace($obj)!="js_boolean") throw new js_exception(new js_typeerror());
        return $obj->value;
    }

}

?>