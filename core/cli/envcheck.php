<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| NexiPress CLI — environment check
|--------------------------------------------------------------------------
| Verifica:
| - PHP e estensioni
| - filesystem runtime (storage)
| - permessi essenziali
|--------------------------------------------------------------------------
*/

return function (array $argv): int {

	$hasErrors = false;

	echo "== NexiPress environment check ==\n\n";

	/*
	|--------------------------------------------------------------------------
	| PHP
	|--------------------------------------------------------------------------
	*/

	echo "-- PHP\n\n";

	if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
		echo "[OK]   PHP version: " . PHP_VERSION . "\n";
	} else {
		echo "[FAIL] PHP version: " . PHP_VERSION . " (>= 8.0 required)\n";
		$hasErrors = true;
	}

	$requiredExt = ['pdo', 'mbstring', 'json', 'intl'];

	foreach ($requiredExt as $ext) {
		if (extension_loaded($ext)) {
			echo "[OK]   Extension: {$ext}\n";
		} else {
			echo "[FAIL] Extension missing: {$ext}\n";
			$hasErrors = true;
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Storage runtime
	|--------------------------------------------------------------------------
	*/

	echo "\n-- Storage runtime\n\n";

	$paths = [
		'storage'        => NP_ROOT . '/storage',
		'storage/cache'  => NP_ROOT . '/storage/cache',
		'storage/log'    => NP_ROOT . '/storage/log',
		'storage/temp'   => NP_ROOT . '/storage/temp',
	];

	foreach ($paths as $label => $path) {

		if (!is_dir($path)) {
			echo "[FAIL] {$label} (missing)\n";
			$hasErrors = true;
			continue;
		}

		if (!is_writable($path)) {
			echo "[FAIL] {$label} (not writable)\n";
			$hasErrors = true;
			continue;
		}

		echo "[OK]   {$label} (writable)\n";
	}

	/*
	|--------------------------------------------------------------------------
	| Config
	|--------------------------------------------------------------------------
	*/

	echo "\n-- Configuration\n\n";

	$required = [
		'config.php' => NP_ROOT . '/config.php',
		'index.php'  => NP_ROOT . '/index.php',
		'.htaccess'  => NP_ROOT . '/.htaccess',
	];

	foreach ($required as $label => $path) {
		if (file_exists($path)) {
			echo "[OK]   {$label} (present)\n";
		} else {
			echo "[FAIL] {$label} (missing)\n";
			$hasErrors = true;
		}
	}


	/*
	|--------------------------------------------------------------------------
	| Result
	|--------------------------------------------------------------------------
	*/

	echo "\n--------------------------------\n\n";
	echo $hasErrors
		? "Environment check FAILED\n"
		: "Environment check PASSED — system OK\n";

		return 0;
	};
