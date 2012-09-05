<?php
setlocale(LC_CTYPE, 'en_US.UTF-8');

$output = null;
$fail = false;

ini_set('display_errors', 'on');
error_reporting(E_ALL | E_STRICT);

if (isset($_POST['code'])) {
	$process = proc_open(__DIR__ . '/min.php', array(
	   0 => array('pipe', 'r'),
	   1 => array('pipe', 'w'),
	), $pipes, __DIR__, array(

	));

	if (is_resource($process)) {
	    fwrite($pipes[0], $_POST['code']);
	    fclose($pipes[0]);

	    $output = stream_get_contents($pipes[1]);
	    fclose($pipes[1]);

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
<style>body{width:980px;margin:25px auto 0;font:16px Georgia,serif}textarea{width:100%;height:175px;font:13px monospace}input{margin:25px 0}</style>
<h1>MinifyJS</h1>
<form action="" method="post">
	<textarea name="code">' . (isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '') . '</textarea>
	<input type="submit" value="Minify!">
</form>';

if (isset($output)) {
	if ($fail) {
		echo '
<pre>' . htmlspecialchars($output) . '</pre>';
	} else {
		echo '
<strong>Success! Original size: ' . strlen($_POST['code']) . ', compressed: ' . strlen($output) . '</strong>
<textarea readonly>' . htmlspecialchars($output) . '</textarea>';
	}
}