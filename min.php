<?php
echo "\n";

if (!defined('MIN_BASE')) {
	define('MIN_BASE', dirname(__FILE__) . '/jsmin/');
}

!defined('DEFINED_TYPE') && define('UNDEFINED_TYPE', 'undefined');

require MIN_BASE . 'parser.php';

$parser = new JSParser();

require 'jsmin/ast.php';

$f = 'jquery-1.7.2.js';
$s = file_get_contents($f);

//echo $s . PHP_EOL;

try {
	$ast = new AST($parser->parse($s, $f, 0));
	$ast->squeeze();
	$tree = $ast->tree()->toString();
	$gzipped = strlen(gzencode($tree, 9));

	//echo implode("\n", array_slice(explode("\n", $tree), 0, 20)) . "\n\n";

	file_put_contents(str_replace('.js', '.min.js', $f), $tree);

	foreach($ast->report() as $line) {
		echo ' * ' . $line . "\n";
	}

	echo "\n\nfrom " . strlen($s) . ' to ' . strlen($tree) . ': ' . -round(((strlen($tree) - strlen($s)) / strlen($s)) * 100) . '% profit';
	echo "\ngzipped: " . $gzipped;
} catch(Exception $e) {
	echo $e->getMessage();
}

echo "\n\n";

function tracer( ) {
	$b = debug_backtrace( );
	array_shift($b);
	$i = '';
	foreach($b as $p) {
		$a = array();
		foreach($p['args'] as $c) {
			if (is_array($c)) {
				$a[] = '[array =' . count($c) . ']';
			} elseif(is_object($c)) {
				$a[] = '[object ' . get_class($c) . ']';
			} elseif(is_string($c)) {
				$l = strpos($c, "\n");
				$a[] = $this->quote($l ? substr($c, 0, $l) . ' ...' : $c, "'");
			} else {
				$a[] = $c;
			}
		}

		echo @($i . ($p['class'] ? $p['class'] . '::' : '') . $p['function'] . '(' . implode(', ', $a) . ')' . ' called at line ' . $p['line']) . "\n";
		$i .= '  ';
	}
}
