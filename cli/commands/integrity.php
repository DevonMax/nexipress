<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| NexiPress CLI — integrity command
|--------------------------------------------------------------------------
| Scopo:
| - verificare che il filesystem sia compatibile con NexiPress
| - verificare che storage sia presente e scrivibile
| - NON modifica nulla
| - NON crea cache
| - NON installa nulla
|--------------------------------------------------------------------------
*/

return function (array $argv): int {

	/*
	|--------------------------------------------------------------------------
	| Funzione: check_fs_tree (PARLANTE)
	|--------------------------------------------------------------------------
	| Regole schema:
	| - 'dir' => []           directory (contenuto libero)
	| - 'dir' => [ ... ]      directory con figli obbligatori
	| - 'file.php' => true   file obbligatorio
	|--------------------------------------------------------------------------
	*/

	function check_fs_tree(
		string $base,
		array $tree,
		bool $mustBeWritable = false,
		string $prefix = ''
	): bool {
		$errors = false;

		foreach ($tree as $name => $children) {

			$path  = rtrim($base, '/') . '/' . $name;
			$label = ltrim($prefix . '/' . $name, '/');

			// FILE
			if ($children === true) {
				if (file_exists($path)) {
					echo "[OK]   {$label}\n";
				} else {
					echo "[FAIL] {$label} (missing file)\n";
					$errors = true;
				}
				continue;
			}

			// DIRECTORY
			if (!is_dir($path)) {
				echo "[FAIL] {$label}/ (missing directory)\n";
				$errors = true;
				continue;
			}

			if ($mustBeWritable && !is_writable($path)) {
				echo "[FAIL] {$label}/ (not writable)\n";
				$errors = true;
				continue;
			}

			echo "[OK]   {$label}/\n";

			if (is_array($children) && $children !== []) {
				if (!check_fs_tree($path, $children, $mustBeWritable, $label)) {
					$errors = true;
				}
			}
		}

		return !$errors;
	}

	echo "== NexiPress integrity check ==\n\n";

	/*
	|--------------------------------------------------------------------------
	| 1. Root filesystem (minimo indispensabile)
	|--------------------------------------------------------------------------
	*/

	echo "-- Root filesystem\n";

	$rootTree = [
		'application' => [],
		'core'        => [],
		'storage'     => [],
		'system'      => [],
		'index.php'   => true,
		'config.php'  => true,
	];

	if (!check_fs_tree(NP_ROOT, $rootTree, false)) {
		echo "\nIntegrity FAILED (root)\n";
		return 1;
	}

	/*
	|--------------------------------------------------------------------------
	| 2. Core minimo eseguibile
	|--------------------------------------------------------------------------
	*/

	echo "\n-- Core filesystem\n";

	$coreTree = [
		'core' => [
			'bootstrap.php'        => true,
			'class.php'            => true,
			'db.php'               => true,
			'env.info.php'         => true,
			'error-handler.php'    => true,
			'function.php'         => true,
			'middleware-loader.php'=> true,
			'route-loader.php'     => true,
			'router-engine.php'    => true,
			'router.php'           => true,
			'assets' => [
				'js' => [
					'lang.js' => true,
				],
			],
		],
	];

	if (!check_fs_tree(NP_ROOT, $coreTree, false)) {
		echo "\nIntegrity FAILED (core)\n";
		return 1;
	}

	/*
	|--------------------------------------------------------------------------
	| 3. Storage runtime (DEVE essere scrivibile)
	|--------------------------------------------------------------------------
	*/

	echo "\n-- Storage runtime\n";

	$storageTree = [
		'storage' => [
			'cache' => [
				'routes.map.php' => true,
			],
			'log'   => [],
			'temp'  => [],
			'web.config.xml' => true,
		],
	];

	if (!check_fs_tree(NP_ROOT, $storageTree, true)) {
		echo "\nIntegrity FAILED (storage)\n";
		return 1;
	}

	/*
	|--------------------------------------------------------------------------
	| 4. System filesystem (rigoroso)
	|--------------------------------------------------------------------------
	*/

	echo "\n-- System filesystem\n";

	$systemTree = [
		'system' => [
			'assets' => [
				'system.css'  => true,
				'system.scss' => true,
			],
			'http_code' => [
				'404.php'      => true,
				'500.php'      => true,
				'debug.php'    => true,
				'fallback.php' => true,
				'translate.php'=> true,
			],
			'locale' => [
				'_lang_switch.controller.php' => true,
				'config-locale.php'           => true,
				'en.php'                      => true,
				'flags' => [],
			],
			'secure.key.php' => true,
		],
	];

	if (!check_fs_tree(NP_ROOT, $systemTree, false)) {
		echo "\nIntegrity FAILED (system)\n";
		return 1;
	}

	/*
	|--------------------------------------------------------------------------
	| SUCCESS
	|--------------------------------------------------------------------------
	*/

	echo "\n--------------------------------\n";
	echo "Integrity check PASSED\n";
	echo "Filesystem is compatible with NexiPress\n";

	return 0;
};
