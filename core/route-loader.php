<?php
/*
|--------------------------------------------------------------------------
| Route Map Loader & Compiler
|--------------------------------------------------------------------------
| Questo modulo:
| - carica routes.map.php
| - valida e normalizza ogni rotta
| - compila le route in regex PHP
| - salva il risultato in cache (routes.cache.php)
|
| Se la cache è più recente della mappa:
| -> viene usata direttamente
|
| NON esegue dispatch
| NON risolve la richiesta HTTP
|--------------------------------------------------------------------------
*/

/**
* Compila la mappa delle rotte e genera la cache.
*
* @return void
*/
function nexi_load_routes_from_map(): void
{
	// ------------------------------------------------------------------
	// File sorgente e file cache
	// ------------------------------------------------------------------
	$mapFile   = alias('approot:routes.map.php', false);
	$cacheFile = alias('approot:routes.cache.php', false);

	// ------------------------------------------------------------------
	// Guard: file mappa obbligatorio
	// ------------------------------------------------------------------
	if (!file_exists($mapFile)) {
		nexi_render_error(
			'File mancante',
			'Il file routes.map.php non esiste.',
			500
		);
		return;
	}

	// ------------------------------------------------------------------
	// Cache validation
	// Cache valida se più recente della mappa
	// ------------------------------------------------------------------
	$useCache = file_exists($cacheFile)
		&& filemtime($cacheFile) >= filemtime($mapFile);

	if ($useCache) {
		require $cacheFile;
		return;
	}

	// ------------------------------------------------------------------
	// Load raw routes map
	// ------------------------------------------------------------------
	$routesRaw = require $mapFile;
	$routes    = [];

	foreach ($routesRaw as $alias => $config) {

		// --------------------------------------------------------------
		// Alias validation
		// Formato richiesto: @nome_alias
		// --------------------------------------------------------------
		if (!is_string($alias) || !preg_match('/^@[a-zA-Z0-9_]+$/', $alias)) {
			nexi_render_error(
				'Alias non valido',
				"Alias '$alias' non conforme.",
				500
			);
			continue;
		}

		// --------------------------------------------------------------
		// Config validation
		// route e target sono obbligatori
		// --------------------------------------------------------------
		if (!is_array($config) || empty($config['route']) || empty($config['target'])) {
			nexi_render_error(
				'Configurazione mancante',
				"Alias $alias: 'route' e 'target' sono obbligatori.",
				500
			);
			continue;
		}

		// --------------------------------------------------------------
		// Normalizzazione parametri
		// --------------------------------------------------------------
		$pattern  = trim($config['route'], '/');
		$method   = strtoupper($config['method'] ?? 'GET');
		$required = !empty($config['required']);
		$types    = [];

		// Target: se non ha namespace, assume app:
		$target = $config['target'];
		if (!str_contains($target, ':') && !str_starts_with($target, '/')) {
			$target = 'app:' . ltrim($target, '/');
		}

		// --------------------------------------------------------------
		// Route → Regex compilation
		// --------------------------------------------------------------
		$segments = explode('/', $pattern);
		$regex    = '#^';

		foreach ($segments as $segment) {

			// Wildcard globale
			if ($segment === '*') {
				$regex .= '/(?P<wildcard>.*)';
				continue;
			}

			// Parametro tipizzato :id(int)
			if (preg_match('/^:([a-zA-Z0-9_]+)\(([^)]+)\)$/', $segment, $m)) {
				$param = $m[1];
				$type  = $m[2];
				$types[$param] = $type;

				$regex .= $required
					? '/(?P<' . $param . '>[^/]+)'
					: '(?:/(?P<' . $param . '>[^/]+))?';
				continue;
			}

			// Parametro semplice :slug
			if (preg_match('/^:([a-zA-Z0-9_]+)$/', $segment, $m)) {
				$param = $m[1];
				$regex .= $required
					? '/(?P<' . $param . '>[^/]+)'
					: '(?:/(?P<' . $param . '>[^/]+))?';
				continue;
			}

			// Segmento statico
			$regex .= '/' . preg_quote($segment, '#');
		}

		$regex .= '/?$#';

		// --------------------------------------------------------------
		// Route finale compilata
		// --------------------------------------------------------------
		$routes[] = [
			'alias'    => $alias,
			'pattern'  => $regex,
			'original' => $pattern,
			'method'   => $method,
			'target'   => $target,
			'types'    => $types,
			'required' => $required,
		];
	}

	// ------------------------------------------------------------------
	// Write cache file (PHP nativo)
	// ------------------------------------------------------------------
	file_put_contents(
		$cacheFile,
		"<?php\nreturn " . var_export($routes, true) . ";\n"
	);
}