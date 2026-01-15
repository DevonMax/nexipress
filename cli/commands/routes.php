<?php
declare(strict_types=1);

return function (array $argv): int {

	$mapFile   = NP_STORAGE . '/cache/routes.map.php';
	$cacheFile = NP_STORAGE . '/cache/routes.cache.php';

	echo "== Routes (MAP → CACHE validation) ==\n\n";

	if (!is_file($mapFile)) {
		echo "[FAIL] routes.map.php not found\n";
		return 1;
	}

	if (!is_file($cacheFile)) {
		echo "[FAIL] routes.cache.php not found\n";
		return 1;
	}

	$map   = require $mapFile;
	$cache = require $cacheFile;

	if (!is_array($map) || empty($map)) {
		echo "[FAIL] routes.map.php empty or invalid\n";
		return 1;
	}

	if (!is_array($cache)) {
		echo "[FAIL] routes.cache.php invalid\n";
		return 1;
	}

	$cacheByAlias = [];
	foreach ($cache as $r) {
		if (isset($r['alias'])) {
			$cacheByAlias[$r['alias']] = $r;
		}
	}

	$hasErrors = false;

	foreach ($map as $alias => $m) {

		$c = $cacheByAlias[$alias] ?? null;

		echo "{$alias}\n\n";
		echo "  MAP:\n\n";
		echo "    method   : " . ($m['method'] ?? 'ANY') . "\n";
		echo "    route    : " . ($m['route'] ?? 'n/a') . "\n";
		echo "    target   : " . ($m['target'] ?? 'n/a') . "\n";
		echo "    required : " . (isset($m['required']) ? ($m['required'] ? 'yes' : 'no') : 'n/a') . "\n\n";

		echo "  CACHE:\n\n";

		if (!$c) {
			echo "    MISSING\n";
			echo "  STATUS: ERROR (route missing in cache)\n\n";
			$hasErrors = true;
			continue;
		}

		echo "    method   : " . ($c['method']   ?? 'n/a') . "\n";
		echo "    route    : " . ($c['original'] ?? 'n/a') . "\n";
		echo "    target   : " . ($c['target']   ?? 'n/a') . "\n";
		echo "    required : " . (isset($c['required']) ? ($c['required'] ? 'yes' : 'no') : 'n/a') . "\n";

		$errors = [];

		// Normalizzazioni
		$mapRoute   = $m['route']   ?? null;
		$cacheRoute = $c['original'] ?? null;

		$mapTarget   = $m['target'] ?? null;
		$cacheTarget = $c['target'] ?? null;

		$mapReq   = $m['required'] ?? null;
		$cacheReq = $c['required'] ?? null;

		$mapMethod   = $m['method'] ?? null;   // opzionale
		$cacheMethod = $c['method'] ?? null;

		// route: MAP route vs CACHE original
		if ($mapRoute !== $cacheRoute) {
			$errors[] = 'route mismatch';
		}

		// target: se MAP è "nudo", accetta anche "app:nudo"
		if ($mapTarget !== null) {
			$targetOk = false;

			if (strpos($mapTarget, ':') !== false) {
				$targetOk = ($mapTarget === $cacheTarget);
			} else {
				$targetOk = ($mapTarget === $cacheTarget) || ('app:' . $mapTarget === $cacheTarget);
			}

			if (!$targetOk) {
				$errors[] = 'target mismatch';
			}
		}

		// required: deve esistere e combaciare (MAP fonte di verità)
		if (array_key_exists('required', $m)) {
			if (!array_key_exists('required', $c) || $mapReq !== $cacheReq) {
				$errors[] = 'required mismatch';
			}
		}

		// method: opzionale, e "ANY" significa skip confronto
		if (array_key_exists('method', $m) && $mapMethod !== null) {
			if (strtoupper((string)$mapMethod) !== 'GET') {
				if (!array_key_exists('method', $c) || $mapMethod !== $cacheMethod) {
					$errors[] = 'method mismatch';
				}
			}
		}


		if ($errors) {
			echo "\n  STATUS: ERROR (" . implode(', ', $errors) . ")\n\n";
			$hasErrors = true;
		} else {
			echo "\n  STATUS: OK\n\n";
		}
		echo "--------------------------------\n\n";

	}

// echo "--------------------------------\n";
echo "Stats:\n\n";
echo "  total routes : " . count($map) . "\n";
echo "  cache routes : " . count($cacheByAlias) . "\n";
echo "  errors       : " . ($hasErrors ? 'yes' : 'no') . "\n\n";
echo "* ANY = method not specified in routes.map.php\n";

	return $hasErrors ? 1 : 0;
};
