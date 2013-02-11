<?php

namespace Walterra\J4p5Bundle\j4p5;

use Walterra\J4p5Bundle\j4p5\jsly;
use Walterra\J4p5Bundle\j4p5\parse\parser;
use Walterra\J4p5Bundle\j4p5\parse\preg_scanner;
use Walterra\J4p5Bundle\j4p5\parse\easy_parser;

/*
known brokenness:
-- no unicode support. PHP has its share of blame for this.
-- line terminators allowed in string literals. mostly because I like it.
-- no magic semi-colons. maybe later.
-- no /regexp/ literals. RegExp() objects are not implemented either, but stubs are there.
-- various deviations from a "pure" ecma-262 grammar. hopefully nobody will notice.
-- various shortcuts in standard functions implementations.
-- slow. The lexer has some retarded matching logic, and the runtime is object-happy.
-- no eval(), no Function(). You don't really need those things anyway.
-- using exceptions can crash php. That will stop once I rewrite jsrt:: to avoid call_user_func().
*/


class jsc {
    static public function gensym($prefix='') {
        static $uniq = 0;
        return $prefix.++$uniq;
    }

    static public function compile($codestr) {
        #-- ideally, we want to avoid generating our parser table at every compilation.
        #-- 2 layers of caching: using an external file, and using static vars.
        global $def_fun_track;
        static $lex = NULL;
        static $parser = NULL;
        if ($lex == NULL) {
            $t0 = microtime(1);

            $t1 = microtime(1);
            $lex = new preg_scanner(0, jsly::$lexp);
            $parser = new easy_parser(jsly::$dpa);
            $t2 = microtime(1);
            #echo "Table loading: ".($t1-$t0)." seconds<br>";
            #echo "Pre generation: ".($t2-$t1)." seconds<br>";
        }
        $t3 = microtime(1);
        $lex->start($codestr);
        $program = $parser->parse("Program", $lex);
        $t4 = microtime(1);
        #echo "Parse time: ".($t4-$t3)." seconds<br>";

        # convert into usable php code
        try {
            $php = $program->emit();
        } catch (\Exception $e) {
            #-- Compilation error. should be pretty rare. usually the parser will barf long before this.
            var_dump($e);
            // echo "Compilation Error: ".$e->value->msg."<hr>";
        }
        return $php;
    }
}

/*
short list of speed optimizations:
- use native PHP boolean, number and string types
-> convert $val->toType() into jsrt::toType($val)
- have specialized emitted code when operand type is known at compile time.
-> ie:(a-b) always return a number, therefore in (a-b)*(c-d), "*" doesn't need to handle non-numbers
*/  
