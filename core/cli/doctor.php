<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| NexiPress CLI — doctor
|--------------------------------------------------------------------------
| Esegue una diagnosi completa del sistema
|--------------------------------------------------------------------------
*/

return function (array $argv): int {

	$commands = ['ping', 'envcheck', 'integrity'];
	$errors = false;

	echo "== NexiPress doctor ==\n\n";

	foreach ($commands as $cmd) {

		echo ">> {$cmd}\n";

		$file = NP_CORE . '/cli/' . $cmd . '.php';
		if (!file_exists($file)) {
			echo "[FAIL] Command file missing: {$cmd}\n\n";
			$errors = true;
			continue;
		}

		$result = require $file;

		if (is_callable($result)) {
			$code = $result([$cmd]);
			if ($code !== 0) {
				$errors = true;
			}
		}

		echo "\n";
	}

	echo "--------------------------------\n";

	if ($errors) {
		echo "Doctor result: ISSUES FOUND\n";
		return 1;
	}

	echo "Doctor result: SYSTEM OK\n";
	return 0;
};