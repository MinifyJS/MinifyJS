#!/usr/bin/php -q
<?php
ini_set('memory_limit', '1G');
date_default_timezone_set('Europe/Amsterdam');

if (!defined('MIN_BASE')) {
	define('MIN_BASE', dirname(__FILE__) . '/jsmin/');
}

!defined('DEFINED_TYPE') && define('UNDEFINED_TYPE', 'undefined');

require MIN_BASE . 'parser.php';

$parser = new JSParser();

require 'jsmin/ast.php';

$options = array(
	'-ncb' => array('crush-bool' => false),
	'--no-crush-bool' => array('crush-bool' => false),

	'-us' => array('unsafe' => true, 'mangle' => true),
	'--unsafe' => array('unsafe' => true, 'mangle' => true),

	'-sc' => array('strip-console' => true),
	'--strip-console' => array('strip-console' => true),

	'-nm' => array('mangle' => false),
	'--no-mangle' => array('mangle' => false)
);

foreach (array_slice($_SERVER['argv'], 1) as $option) {
	if (isset($options[$option])) {
		foreach($options[$option] as $what => $value) {
			AST::$options[$what] = $value;
		}
	} else {
		$f = $option;
	}
}

if (!isset($f) || !is_file($f)) {
	throw new Exception('Unknown file');
}

$s = file_get_contents($f);

$timers = array();

$t = microtime(true);
$tree = $parser->parse($s, $f, 0);
$timers['parse'] = microtime(true) - $t;

$t = microtime(true);
$ast = new AST($tree);
$timers['ast'] = microtime(true) - $t;

$t = microtime(true);
$ast->squeeze();
$timers['squeeze'] = microtime(true) - $t;

$t = microtime(true);
$tree = $ast->toString();
$timers['tostring'] = microtime(true) - $t;

echo $tree;
