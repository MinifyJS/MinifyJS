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

$defines = array();

$options = array(
	'-ncb' => array('crush-bool' => false),
	'--no-crush-bool' => array('crush-bool' => false),

	'-us' => array('unsafe' => true, 'mangle' => true),
	'--unsafe' => array('unsafe' => true, 'mangle' => true),

	'-nm' => array('mangle' => false),
	'--no-mangle' => array('mangle' => false),

	'-t' => array('timer' => true),
	'--timer' => array('timer' => true),

	'-b' => array('beautify' => true),
	'--beautify' => array('beautify' => true),

	'-nc' => array('no-copyright' => true),
	'--no-copyright' => array('no-copyright' => true),

	'-sd' => array('strip-debug' => true),
	'--strip-debug' => array('strip-debug' => true),

	'-ni' => array('no-inlining' => true),
	'--no-inlining' => array('no-inlining' => true),

	'-uws' => array('unicode-ws' => true),
	'--unicode-whitespace' => array('unicode-ws' => true),

	'-tl' => array('toplevel' => true),
	'--toplevel' => array('toplevel' => true),

	'-p' => array('profile' => true),
	'--profile' => array('profile' => true),

	'-q' => array('silent' => true),
	'--quiet' => array('silent' => true)
);

for ($i = 1, $length = count($_SERVER['argv']); $i < $length; ++$i) {
	$option = $_SERVER['argv'][$i];

	if ($option === '--define' || $option == '-d') {
		$name = $_SERVER['argv'][$i + 1];
		$tree = $parser->parse($_SERVER['argv'][$i + 2], '[cmd]', 1, AST::$options['unicode-ws']);
		$ast = new AST($tree);
		$defines[$name] = $ast->tree()->rootElement();

		$i += 2;
	} elseif (isset($options[$option])) {
		foreach($options[$option] as $what => $value) {
			AST::$options[$what] = $value;
		}
	} elseif ($option[0] === '-') {
		throw new Exception('Invalid option ' . $option);
	} else {
		$f = $option;
	}
}

if (!isset($f) || $f === '-') {
	$s = '';
	$fd = defined('STDIN') ? STDIN : fopen('php://stdin', 'r');

	while (false !== ($line = fgets($fd))) {
		$s .= $line;
	}

	if (!defined('STDIN')) {
		fclose($fd);
	}

	$f = '[stdin]';
} elseif (!is_file($f)) {
	throw new Exception('Unknown file');
} else {
	$s = trim(file_get_contents($f));
}

$timers = array();

if (AST::$options['profile'] && function_exists('xhprof_enable')) {
    xhprof_enable(XHPROF_FLAGS_MEMORY, array('ignored_functions' =>  array(
        'call_user_func',
        'call_user_func_array'
    )));

    register_shutdown_function(function () {
        $data = xhprof_disable();

        global $xhprof_content;

        require_once 'xhprof/utils/xhprof_lib.php';
        require_once 'xhprof/utils/xhprof_runs.php';

        if (empty($GLOBALS['wasError'])) {
            $runs = new XHProfRuns_Default();
            $runs->save_run($data, 'MinifyJS');
        }
    });
}

try {
	$t = microtime(true);
	$tree = $parser->parse($s, $f, 1, AST::$options['unicode-ws']);
	$timers['parse'] = microtime(true) - $t;

	$t = microtime(true);
	$ast = new AST($tree, $defines);
	$timers['ast'] = microtime(true) - $t;


	$t = microtime(true);
	$ast->squeeze();
	$timers['squeeze'] = microtime(true) - $t;

	$t = microtime(true);
	$tree = $ast->toString();
	$timers['tostring'] = microtime(true) - $t;

	if (!AST::$options['no-copyright']) {
		$tree = array_reduce($parser->getLicenses(), function ($a, $b) {
			if ($a === null) {
				return $b;
			}

			return substr($a, 0, -1) . substr($b, 1);
		}) . $tree;
	}

	$timers['profit'] = array(
		'old' => strlen($s),
		'new' => strlen($tree)
	);

	if (function_exists('gzencode')) {
		$timers['profit'] = array(
			'old' => array(
				'normal' => $timers['profit']['old'],
				'gzip' => strlen(gzencode($s, 9))
			),
			'new' => array(
				'normal' => $timers['profit']['new'],
				'gzip' => strlen(gzencode($tree, 9))
			)
		);
	}

	if (AST::$options['timer']) {
		print_r($timers);
	} else {
		echo AST::$options['beautify'] ? Stream::unindent($tree) : $tree;
	}
} catch (Exception $e) {
	echo $e->getMessage();

	exit(1);
}
