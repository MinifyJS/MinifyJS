#!/usr/bin/env php
<?php
chdir(__DIR__);

const STATE_META = 0;
const STATE_KEY = 1;
const STATE_DATA = 2;

function test($info) {
	$cmd = 'echo ' . escapeshellarg($info['data']['INPUT']) . ' | ../min.php';
	foreach($info['meta']['options'] as $option) {
		$cmd .= ' --' . $option;
	}

	$out = shell_exec($cmd);
	$result = $out === $info['data']['EXPECT'];

	echo ' * ' . $info['meta']['name'] . ': ' . ($result ? 'pass' : 'FAIL') . "\n";
	if (!$result) {
		echo '  expect: ' . $info['data']['EXPECT'] . "\n";
		echo '  gotten: ' . $out . "\n\n";
	}
}

echo 'MinifyJS simple test suite' . "\n";

foreach(glob('unit/*.test') as $file) {
	$state = STATE_META;

	$info = array(
		'meta' => array(),
		'data' => array()
	);

	foreach (preg_split(
		'~(*ANYCRLF)(?m)^=== ([A-Z]+)$~',
		file_get_contents($file),
		-1,
		PREG_SPLIT_DELIM_CAPTURE
	) as $line) {
		$line = trim($line);

		switch ($state) {
		case STATE_META:
			$info['meta'] = json_decode($line, true);
			$state = STATE_KEY;
			break;
		case STATE_KEY:
			$key = $line;
			$state = STATE_DATA;
			break;
		case STATE_DATA:
			$info['data'][$key] = $line;
			$state = STATE_KEY;
			break;
		default:
			throw new Exception('Weird state');
		}
	}

	test($info);
}
