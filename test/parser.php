<?php
// Parser tests copied from UglifyJS
// License: https://github.com/mishoo/UglifyJS#license
$tests = array(
	array("var abc;", "Regular variable statement w/o assignment"),
	array("var abc = 5;", "Regular variable statement with assignment"),
	array("/* */;", "Multiline comment"),
	array('/** **/;', 'Double star multiline comment'),
	array("var f = function(){;};", "Function expression in var assignment"),
	array('hi; // moo\n;', 'single line comment'),
	array('var varwithfunction;', 'Dont match keywords as substrings'), // difference between `var withsomevar` and `"str"` (local search and lits)
	array('a + b;', 'addition'),
	array("'a';", 'single string literal'),
	array("'a\\n';", 'single string literal with escaped return'),
	array('"a";', 'double string literal'),
	array('"a\\n";', 'double string literal with escaped return'),
	array('"var";', 'string is a keyword'),
	array('"variable";', 'string starts with a keyword'),
	array('"somevariable";', 'string contains a keyword'),
	array('"somevar";', 'string ends with a keyword'),
	array('500;', 'int literal'),
	array('500.;', 'float literal w/o decimals'),
	array('500.432;', 'float literal with decimals'),
	array('.432432;', 'float literal w/o int'),
	array('(a,b,c);', 'parens and comma'),
	array('[1,2,abc];', 'array literal'),
	array('var o = {a:1};', 'object literal unquoted key'),
	array('var o = {"b":2};', 'object literal quoted key'), // opening curly may not be at the start of a statement...
	array('var o = {c:c};', 'object literal keyname is identifier'),
	array('var o = {a:1,"b":2,c:c};', 'object literal combinations'),
	array("var x;\nvar y;", 'two lines'),
	array("var x;\nfunction n(){; }", 'function def'),
	array("var x;\nfunction n(abc){; }", 'function def with arg'),
	array("var x;\nfunction n(abc, def){ ;}", 'function def with args'),
	array('function n(){ "hello"; }', 'function def with body'),
	array('/a/;', 'regex literal'),
	array('/a/b;', 'regex literal with flag'),
	array('/a/ / /b/;', 'regex div regex'),
	array('a/b/c;', 'triple division looks like regex'),
	array('+function(){/regex/;};', 'regex at start of function body'),
	// http://code.google.com/p/es-lab/source/browse/trunk/tests/parser/parsertests.js?r=86
	// http://code.google.com/p/es-lab/source/browse/trunk/tests/parser/parsertests.js?r=430

	// first tests for the lexer, should also parse as program (when you append a semi)

	// comments
	array('//foo!@#^&$1234\nbar;', 'single line comment'),
	array('/* abcd!@#@$* { } && null*/;', 'single line multi line comment'),
	array("/*foo\nbar*/;",'multi line comment'),
	array('/*x*x*/;','multi line comment with *'),
	array('/**/;','empty comment'),
	// identifiers
	array("x;",'1 identifier'),
	array("_x;",'2 identifier'),
	array("xyz;",'3 identifier'),
	array('$x;','4 identifier'),
	array("x$;",'5 identifier'),
	array("_;",'6 identifier'),
	array("x5;",'7 identifier'),
	array("x_y;",'8 identifier'),
	array("x+5;",'9 identifier'),
	array("xyz123;",'10 identifier'),
	array("x1y1z1;",'11 identifier'),
	array("ø,print(ø)",'12 unicode identifier'),
	array("foo\\u00D8bar;",'13 identifier unicode escape'),
	array("fooøbar;",'14 identifier unicode embedded'),
	// numbers
	array("5;", '1 number'),
	array("5.5;", '2 number'),
	array("0;", '3 number'),
	array("0.0;", '4 number'),
	array("0.001;", '5 number'),
	array("1.e2;", '6 number'),
	array("1.e-2;", '7 number'),
	array("1.E2;", '8 number'),
	array("1.E-2;", '9 number'),
	array(".5;", '10 number'),
	array(".5e3;", '11 number'),
	array(".5e-3;", '12 number'),
	array("0.5e3;", '13 number'),
	array("55;", '14 number'),
	array("123;", '15 number'),
	array("55.55;", '16 number'),
	array("55.55e10;", '17 number'),
	array("123.456;", '18 number'),
	array("1+e;", '20 number'),
	array("0x01;", '22 number'),
	array("0XCAFE;", '23 number'),
	array("0x12345678;", '24 number'),
	array("0x1234ABCD;", '25 number'),
	array("0x0001;", '26 number'),
	// strings
	array("\"foo\";", '1 string'),
	array("'foo';", '2 string'),
	array("\"x\";", '3 string'),
	array("'';", '4 string'),
	array('"foo\tbar";', '5 string'),
	array("\"!@#$%^&*()_+{}[]\";", '6 string'),
	array("\"/*test*/\";", '7 string'),
	array("\"//test\";", '8 string'),
	array("\"\\\\\";", '9 string'),
	array("\"\\u0001\";", '10 string'),
	array("\"\\uFEFF\";", '11 string'),
	array("\"\\u10002\";", '12 string'),
	array("\"\\x55\";", '13 string'),
	array("\"\\x55a\";", '14 string'),
	array("\"a\\\\nb\";", '15 string'),
	array('";"', '16 string: semi in a string'),
	array("\"a\\\nb\";", '17 string: line terminator escape'),
	// literals
	array("null;", "null"),
	array("true;", "true"),
	array("false;", "false"),
	// regex
	array("/a/;", "1 regex"),
	array("/abc/;", "2 regex"),
	array("/abc[a-z]*def/g;", "3 regex"),
	array("/\\b/;", "4 regex"),
	array("/[a-zA-Z]/;", "5 regex"),

	// program tests (for as far as they havent been covered above)

	// regexp
	array("/foo(.*)/g;", "another regexp"),
	// arrays
	array("[];", "1 array"),
	array("[   ];", "2 array"),
	array("[1];", "3 array"),
	array("[1,2];", "4 array"),
	array("[1,2,,];", "5 array"),
	array("[1,2,3];", "6 array"),
	array("[1,2,3,,,];", "7 array"),
	// objects
	array("{};", "1 object"),
	array("({x:5});", "2 object"),
	array("({x:5,y:6});", "3 object"),
	array("({x:5,});", "4 object"),
	array("({if:5});", "5 object"),
	array("({ get x() {42;} });", "6 object"),
	array("({ set y(a) {1;} });", "7 object"),
	// member expression
	array("o.m;", "1 member expression"),
	array("o['m'];", "2 member expression"),
	array("o['n']['m'];", "3 member expression"),
	array("o.n.m;", "4 member expression"),
	array("o.if;", "5 member expression"),
	// call and invoke expressions
	array("f();", "1 call/invoke expression"),
	array("f(x);", "2 call/invoke expression"),
	array("f(x,y);", "3 call/invoke expression"),
	array("o.m();", "4 call/invoke expression"),
	array("o['m'];", "5 call/invoke expression"),
	array("o.m(x);", "6 call/invoke expression"),
	array("o['m'](x);", "7 call/invoke expression"),
	array("o.m(x,y);", "8 call/invoke expression"),
	array("o['m'](x,y);", "9 call/invoke expression"),
	array("f(x)(y);", "10 call/invoke expression"),
	array("f().x;", "11 call/invoke expression"),

	// eval
	array("eval('x');", "1 eval"),
	array("(eval)('x');", "2 eval"),
	array("(1,eval)('x');", "3 eval"),
	array("eval(x,y);", "4 eval"),
	// new expression
	array("new f();", "1 new expression"),
	array("new o;", "2 new expression"),
	array("new o.m;", "3 new expression"),
	array("new o.m(x);", "4 new expression"),
	array("new o.m(x,y);", "5 new expression"),
	// prefix/postfix
	array("++x;", "1 pre/postfix"),
	array("x++;", "2 pre/postfix"),
	array("--x;", "3 pre/postfix"),
	array("x--;", "4 pre/postfix"),
	array("x ++;", "5 pre/postfix"),
	array("x /* comment */ ++;", "6 pre/postfix"),
	array("++ /* comment */ x;", "7 pre/postfix"),
	// unary operators
	array("delete x;", "1 unary operator"),
	array("void x;", "2 unary operator"),
	array("+ x;", "3 unary operator"),
	array("-x;", "4 unary operator"),
	array("~x;", "5 unary operator"),
	array("!x;", "6 unary operator"),
	// meh
	array("new Date++;", "new date ++"),
	array("+x++;", " + x ++"),
	// expression expressions
	array("1 * 2;", "1 expression expressions"),
	array("1 / 2;", "2 expression expressions"),
	array("1 % 2;", "3 expression expressions"),
	array("1 + 2;", "4 expression expressions"),
	array("1 - 2;", "5 expression expressions"),
	array("1 << 2;", "6 expression expressions"),
	array("1 >>> 2;", "7 expression expressions"),
	array("1 >> 2;", "8 expression expressions"),
	array("1 * 2 + 3;", "9 expression expressions"),
	array("(1+2)*3;", "10 expression expressions"),
	array("1*(2+3);", "11 expression expressions"),
	array("x<y;", "12 expression expressions"),
	array("x>y;", "13 expression expressions"),
	array("x<=y;", "14 expression expressions"),
	array("x>=y;", "15 expression expressions"),
	array("x instanceof y;", "16 expression expressions"),
	array("x in y;", "17 expression expressions"),
	array("x&y;", "18 expression expressions"),
	array("x^y;", "19 expression expressions"),
	array("x|y;", "20 expression expressions"),
	array("x+y<z;", "21 expression expressions"),
	array("x<y+z;", "22 expression expressions"),
	array("x+y+z;", "23 expression expressions"),
	array("x+y<z;", "24 expression expressions"),
	array("x<y+z;", "25 expression expressions"),
	array("x&y|z;", "26 expression expressions"),
	array("x&&y;", "27 expression expressions"),
	array("x||y;", "28 expression expressions"),
	array("x&&y||z;", "29 expression expressions"),
	array("x||y&&z;", "30 expression expressions"),
	array("x<y?z:w;", "31 expression expressions"),
	// assignment
	array("x >>>= y;", "1 assignment"),
	array("x <<= y;", "2 assignment"),
	array("x = y;", "3 assignment"),
	array("x += y;", "4 assignment"),
	array("x /= y;", "5 assignment"),
	// comma
	array("x, y;", "comma"),
	// block
	array("{};", "1 block"),
	array("{x;};", "2 block"),
	array("{x;y;};", "3 block"),
	// vars
	array("var x;", "1 var"),
	array("var x,y;", "2 var"),
	array("var x=1,y=2;", "3 var"),
	array("var x,y=2;", "4 var"),
	// empty
	array(";", "1 empty"),
	array("\n;", "2 empty"),
	// expression statement
	array("x;", "1 expression statement"),
	array("5;", "2 expression statement"),
	array("1+2;", "3 expression statement"),
	// if
	array("if (c) x; else y;", "1 if statement"),
	array("if (c) x;", "2 if statement"),
	array("if (c) {} else {};", "3 if statement"),
	array("if (c1) if (c2) s1; else s2;", "4 if statement"),
	// while
	array("do s; while (e);", "1 while statement"),
	array("do { s; } while (e);", "2 while statement"),
	array("while (e) s;", "3 while statement"),
	array("while (e) { s; };", "4 while statement"),
	// for
	array("for (;;) ;", "1 for statement"),
	array("for (;c;x++) x;", "2 for statement"),
	array("for (i;i<len;++i){};", "3 for statement"),
	array("for (var i=0;i<len;++i) {};", "4 for statement"),
	array("for (var i=0,j=0;;){};", "5 for statement"),
	//["for (x in b; c; u) {};", "6 for statement"),
	array("for ((x in b); c; u) {};", "7 for statement"),
	array("for (x in a);", "8 for statement"),
	array("for (var x in a){};", "9 for statement"),
	array("for (var x=5 in a) {};", "10 for statement"),
	array("for (var x = a in b in c) {};", "11 for statement"),
	array("for (var x=function(){a+b;}; a<b; ++i) some;", "11 for statement, testing for parsingForHeader reset with the function"),
	array("for (var x=function(){for (x=0; x<15; ++x) alert(foo); }; a<b; ++i) some;", "11 for statement, testing for parsingForHeader reset with the function"),
	// flow statements
	array("while(1){ continue; }", "1 flow statement"),
	array("label: while(1){ continue label; }", "2 flow statement"),
	array("while(1){ break; }", "3 flow statement"),
	array("somewhere: while(1){ break somewhere; }", "4 flow statement"),
	array("while(1){ continue /* comment */ ; }", "5 flow statement"),
	array("while(1){ continue \n; }", "6 flow statement"),
	array("(function(){ return; })()", "7 flow statement"),
	array("(function(){ return 0; })()", "8 flow statement"),
	array("(function(){ return 0 + \n 1; })()", "9 flow statement"),
	// with
	array("with (e) s;", "with statement"),
	// switch
	array("switch (e) { case x: s; };", "1 switch statement"),
	array("switch (e) { case x: s1;s2; default: s3; case y: s4; };", "2 switch statement"),
	array("switch (e) { default: s1; case x: s2; case y: s3; };", "3 switch statement"),
	array("switch (e) { default: s; };", "4 switch statement"),
	array("switch (e) { case x: s1; case y: s2; };", "5 switch statement"),
	// labels
	array("foo : x;", " flow statement"),
	// throw
	array("throw x;", "1 throw statement"),
	array("throw x\n;", "2 throw statement"),
	// try catch finally
	array("try { s1; } catch (e) { s2; };", "1 trycatchfinally statement"),
	array("try { s1; } finally { s2; };", "2 trycatchfinally statement"),
	array("try { s1; } catch (e) { s2; } finally { s3; };", "3 trycatchfinally statement"),
	// debugger
	array("debugger;", "debugger statement"),
	// function decl
	array("function f(x) { e; return x; };", "1 function declaration"),
	array("function f() { x; y; };", "2 function declaration"),
	array("function f(x,y) { var z; return x; };", "3 function declaration"),
	// function exp
	array("(function f(x) { return x; });", "1 function expression"),
	array("(function empty() {;});", "2 function expression"),
	array("(function empty() {;});", "3 function expression"),
	array("(function (x) {; });", "4 function expression"),
	// program
	array("var x; function f(){;}; null;", "1 program"),
	array(";;", "2 program"),
	array("{ x; y; z; }", "3 program"),
	array("function f(){ function g(){;}};", "4 program"),
	array("x;\n/*foo*/\n	;", "5 program"),

	// asi
	array("foo: while(1){ continue \n foo; }", "1 asi"),
	array("foo: while(1){ break \n foo; }", "2 asi"),
	array("(function(){ return\nfoo; })()", "3 asi"),
	array("var x; { 1 \n 2 } 3", "4 asi"),
	array("ab 	 /* hi */\ncd", "5 asi"),
	array("ab/*\n*/cd", "6 asi (multi line multilinecomment counts as eol)"),
	array("foo: while(1){ continue /* wtf \n busta */ foo; }", "7 asi illegal with multi line comment"),
	array("function f() { s }", "8 asi"),
	array("function f() { return }", "9 asi"),

	// use strict
	// XXX: some of these should actually fail?
	//      no support for "use strict" yet...
	array("\"use strict\"; 'bla'\n; foo;", "1 directive"),
	array("(function() { \"use strict\"; 'bla';\n foo; });", "2 directive"),
	array('"use\\n strict";', "3 directive"),
	array('foo; "use strict";', "4 directive"),

	// tests from http://es5conform.codeplex.com/

	array('"use strict"; var o = { eval: 42};', "8.7.2-3-1-s: the use of eval as property name is allowed"),
	array('({foo:0,foo:1});', 'Duplicate property name allowed in not strict mode'),
	array('function foo(a,a){}', 'Duplicate parameter name allowed in not strict mode'),
	array('(function foo(eval){})', 'Eval allowed as parameter name in non strict mode'),
	array('(function foo(arguments){})', 'Arguments allowed as parameter name in non strict mode'),

	// empty programs

	array('', '1 Empty program'),
	array('// test', '2 Empty program'),
	array("//test\n", '3 Empty program'),
	array("\n// test", '4 Empty program'),
	array("\n// test\n", '5 Empty program'),
	array('/* */', '6 Empty program'),
	array("/*\ns,fd\n*/", '7 Empty program'),
	array("/*\ns,fd\n*/\n", '8 Empty program'),
	array('  	', '9 Empty program'),
	array("  /*\nsmeh*/	\n   ", '10 Empty program'),

	// trailing whitespace

	array('a  ', '1 Trailing whitespace'),
	array('a /* something */', '2 Trailing whitespace'),
	array("a\n	// hah", '3 Trailing whitespace'),
	array('/abc/de//f', '4 Trailing whitespace'),
	array("/abc/de/*f*/\n	", '5 Trailing whitespace'),

	// things the parser tripped over at one point or the other (prevents regression bugs)
	array("for (x;function(){ a\nb };z) x;", 'for header with function body forcing ASI'),
	array('c=function(){return;return};', 'resetting noAsi after literal'),
	array("d\nd()", 'asi exception causing token overflow'),
	array('for(;;){x=function(){}}', 'function expression in a for header'),
	array('for(var k;;){}', 'parser failing due to ASI accepting the incorrect "for" rule'),
	array('({get foo(){ }})', 'getter with empty function body'),
	array("\nreturnr", 'eol causes return statement to ignore local search requirement'),
	array(' / /', '1 whitespace before regex causes regex to fail?'),
	array('/ // / /', '2 whitespace before regex causes regex to fail?'),
	array('/ / / / /', '3 whitespace before regex causes regex to fail?'),

	array("\n\t// Used for trimming whitespace\n\ttrimLeft = /^\\s+/;trimRight = /\\s+$/;\t\n",'turned out this didnt crash (the test below did), but whatever.'),
	array('/[\\/]/;', 'escaped forward slash inside class group (would choke on fwd slash)'),
	array('/[/]/;', 'also broke but is valid in es5 (not es3)'),
	array('({get:5});','get property name thats not a getter'),
	array('({set:5});','set property name thats not a setter'),
	array('l !== "px" && (d.style(h, c, (k || 1) + l), j = (k || 1) / f.cur() * j, d.style(h, c, j + l)), i[1] && (k = (i[1] === "-=" ? -1 : 1) * k + j), f.custom(j, k, l)', 'this choked regex/div at some point'),
	array('(/\'/g, \'\\\\\\\'\') + "\'";', 'the sequence of escaped characters confused the tokenizer'),
	array('if (true) /=a/.test("a");', 'regexp starting with "=" in not obvious context (not implied by preceding token)')
);

if (!defined('MIN_BASE')) {
	define('MIN_BASE', dirname(__DIR__) . '/jsmin/');
}

require MIN_BASE . 'parser.php';

$parser = new JSParser();

foreach($tests as $i => $test) {
	try {
		$parser->parse($test[0], '[test]', 1, true);
		echo 'ok ' . $i . ': ' . $test[1] . "\n";
	} catch(Exception $e) {
		echo 'FAIL ' . $i . ': ' . $test[1] . "\n" . preg_replace('~^~m', '  ', $e->getMessage()) . "\n";
	}
}
