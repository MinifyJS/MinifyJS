<?php
setlocale(LC_CTYPE, 'en_US.UTF-8');

$output = null;
$fail = false;

ini_set('display_errors', 'on');
error_reporting(E_ALL | E_STRICT);

if (isset($_POST['code'])) {
	$options = '';
	if (isset($_POST['options']) && is_array($_POST['options'])) {
		foreach(array_keys($_POST['options']) as $option) {
			switch ($option) {
			case 'ncb':
				$options .= ' --no-crush-bool';
				break;
			case 'us':
				$options .= ' --unsafe';
				break;
			case 'nm':
				$options .= ' --no-mangle';
				break;
			case 'b':
				$options .= ' --beautify';
				break;
			case 'sd':
				$options .= ' --strip-debug';
				break;
			case 'tl':
				$options .= ' --toplevel';
				break;
			case 'ns':
				$options .= ' --no-squeeze';
				break;
			}
		}
	}

	$process = proc_open(__DIR__ . '/min.php' . $options, array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w'),
		2 => array('pipe', 'w')
	), $pipes, __DIR__, array(

	));

	if (is_resource($process)) {
		fwrite($pipes[0], $_POST['code']);
		fclose($pipes[0]);

		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$errors = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$return = proc_close($process);

		if ($return !== 0) {
			$fail = true;
		}
	} else {
		$output = 'No process';
		$fail = true;
	}
}

echo '<!DOCTYPE html>
<meta charset="UTF-8">
<title>MinifyJS</title>
<style>body{width:980px;margin:25px auto 0;font:16px Georgia,serif}textarea{width:100%;height:175px;font:13px monospace}input,p{margin:5px 0;}input[type=submit]{margin:25px 0}</style>
<h1><a href="https://github.com/MinifyJS/MinifyJS">MinifyJS</a></h1>
<form action="" method="post">
 <textarea name="code">' . (isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '') . '</textarea>
 <p>';
foreach(array(array(
	'ncb' => "Don't crush booleans",
	'us' => 'Unsafe transformations',
	'nm' => 'No mangling'
), array(
	'b' => 'Beautify',
	'sd' => 'Strip debug expressions',
	'tl' => 'Mangle toplevel variables'
), array(
	'ns' => 'No squeezing'
)) as $options) {
	foreach($options as $option => $name) {
		echo '
  <input type="checkbox" name="options[' . $option . ']" value="1"' . (isset($_POST['options'][$option]) ? ' checked' : '') . ' id="' . $option . '"> <label for="' . $option . '">' . $name . '</label>';
	}
	echo '<br>';
}
echo '
 </p>
 <input type="submit" value="Minify!">
</form>';

if (isset($output)) {
	if ($fail) {
		echo '
<pre>' . htmlspecialchars($output) . '</pre>';
	} else {
		if ($errors) {
			echo '<pre>' . htmlspecialchars($errors) . '</pre>';
		}

		echo '
<strong>Success! Original size: ' . strlen($_POST['code']) . ', compressed: ' . strlen($output) . '</strong>
<textarea readonly>' . htmlspecialchars($output) . '</textarea>';
	}
}
