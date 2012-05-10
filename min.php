#!/usr/bin/env php
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

	'-nm' => array('mangle' => false),
	'--no-mangle' => array('mangle' => false),

	'-t' => array('timer' => true),
	'--timer' => array('timer' => true),

	'-b' => array('beautify' => true, 'crush-bool' => false),
	'--beautify' => array('beautify' => true, 'crush-bool' => false),

	'-nc' => array('no-copyright' => true),
	'--no-copyright' => array('no-copyright' => true),

	'-sd' => array('strip-debug' => true),
	'--strip-debug' => array('strip-debug' => true),

	'-ni' => array('no-inlining' => true),
	'--no-inlining' => array('no-inlining' => true),

	'-uws' => array('unicode-ws' => true),
	'--unicode-whitespace' => array('unicode-ws' => true)
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

$s = trim(file_get_contents($f));

$timers = array();

try {
	$t = microtime(true);
	$tree = $parser->parse($s, $f, 1, AST::$options['unicode-ws']);
	$timers['parse'] = microtime(true) - $t;

	$t = microtime(true);
	$ast = new AST($tree);
	$timers['ast'] = microtime(true) - $t;

if (function_exists('xhprof_enable')) {
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



	$t = microtime(true);
	$ast->squeeze();
	$timers['squeeze'] = microtime(true) - $t;

	$t = microtime(true);
	$tree = $ast->toString();
	$timers['tostring'] = microtime(true) - $t;

	$timers['profit'] = array(
		'old' => strlen($s),
		'new' => strlen($tree)
	);

	if (AST::$options['timer']) {
		print_r($timers);
	} else {
		if (!AST::$options['no-copyright']) {
			foreach($parser->getLicenses() as $license) {
				echo $license;
			}
		}

		echo $tree;
	}
} catch (Exception $e) {
	echo $e->getMessage();

	exit(1);
}
