<?php
/**
* Classe Route
*
* Router runtime di NexiPress basato su routing dichiarativo mappato.
*
* Questa classe NON definisce le route e NON le parsa:
* utilizza esclusivamente le route già compilate e cache-izzate
* (routes.map.php → routes.cache.php) per risolvere una richiesta HTTP.
*
* Responsabilità principali:
* - gestione del ciclo di dispatch delle route mappate
* - esecuzione dei middleware globali before / after
* - validazione semantica delle route (required, tipi parametri)
* - risoluzione e inclusione del controller corretto
* - popolamento del contesto di routing (ctx)
*
* Caratteristiche chiave:
* - una sola fonte di verità (routing dichiarativo)
* - cache-first, nessun parsing a runtime
* - language-aware (usa il contesto lingua attivo)
* - orientata al CMS, non a un framework general purpose
*
* La classe è intenzionalmente focalizzata sul runtime:
* non contiene logica di definizione, build o configurazione delle route.
*
* Versione: NexiPress Router 0.9.0
*/

class Route
{
	/*
	 * Registra una funzione middleware da eseguire prima del dispatch della rotta
	 * Può essere usato per autenticazione, logging, validazioni globali ecc.
	 * @param callable $fn Funzione da eseguire prima del ciclo di routing
	 */
	public static function before(callable $fn): void
	{
		self::$beforeHooks[] = $fn;
	}

	/*
	* Esegue tutti i middleware registrati con 'when' => 'before'.
	* Viene richiamato all'inizio del ciclo di dispatch, prima del controller.
	*/
	public static function run_before(): void
	{
		foreach (self::$beforeHooks as $fn) {
			call_user_func($fn);
		}
	}

	/*
	 * Registra una funzione middleware da eseguire dopo il caricamento del controller
	 * Utile per logging, post-elaborazione, o cleanup finale
	 * @param callable $fn Funzione da eseguire dopo l'inclusione del controller
	 */
	public static function after(callable $fn): void
	{
		self::$afterHooks[] = $fn;
	}

	/*
	* Esegue tutti i middleware registrati con 'when' => 'after'.
	* Viene richiamato alla fine del ciclo di dispatch, dopo il controller.
	*/
	public static function run_after(): void
	{
		foreach (self::$afterHooks as $fn) {
			call_user_func($fn);
		}
	}

	protected static array $beforeHooks = [];
	protected static array $afterHooks  = [];

public static function dispatch_map(): bool
{
	$page = ($p = '/' . ltrim($_GET['page'] ?? 'home', '/')) === '/' ? '/home' : $p;
	$page = nexi_transliterate($page, ctx::get('lang.current') ?? 'en');
	self::run_before();

	$routes = nexi_load_routes_map_cache();
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

	foreach ($routes as $route) {

		if ($route['method'] !== $method) {
			continue;
		}

		if (!preg_match($route['pattern'], $page, $matches)) {

			if (is_debug() && (($route['required'] ?? false) === true)) {

				$expected = substr_count($route['original'], ':');
				$urlParts = explode('/', trim($page, '/'));
				$routeParts = explode('/', trim($route['original'], '/'));

				$routeStaticParts = [];
				foreach ($routeParts as $part) {
					if (str_starts_with($part, ':')) break;
					$routeStaticParts[] = $part;
				}

				$isSamePrefix = array_slice($urlParts, 0, count($routeStaticParts)) === $routeStaticParts;

				if ($isSamePrefix) {

					$routeStaticCount = count($routeStaticParts);
					$actual = count($urlParts) - $routeStaticCount;

					if ($actual < $expected) {

						if (Config::get('routing_log')) {
							nexi_debug_log([
								'result'  => 'ERROR',
								'page'    => $page,
								'pattern' => $route['original'],
								'error'   => 'ROUTE_INCOMPLETE_PRE_MATCH'
							]);
						}

						ctx::set('route.path', $route['original']);
						nexi_render_error_safe(
							nexi_lang('route_incomplete_title'),
							nexi_lang('route_incomplete_message', $page),
							422,
							null,
							null,
							null,
							$route['original']
						);
					}
				}
			}

			continue;
		}

		ctx::set('route.path', $route['original']);

		if (isset($route['required']) && $route['required'] === true) {

			$expected = substr_count($route['original'], ':');

			$urlParts = explode('/', trim($page, '/'));
			$routeParts = explode('/', trim($route['original'], '/'));

			$routeStatic = 0;
			foreach ($routeParts as $part) {
				if (str_starts_with($part, ':')) break;
				$routeStatic++;
			}

			$actual = count($urlParts) - $routeStatic;

			if ($actual < $expected) {

				if (Config::get('routing_log')) {
					nexi_debug_log([
						'result'  => 'ERROR',
						'page'    => $page,
						'pattern' => $route['original'],
						'error'   => 'ROUTE_INCOMPLETE_POST_MATCH'
					]);
				}

				nexi_render_error_safe(
					nexi_lang('route_incomplete_title'),
					nexi_lang('route_incomplete_message', $page),
					422,
					null,
					null,
					null,
					$route['original']
				);
			}
		}

		$params = array_filter(
			$matches,
			fn($k) => !is_int($k),
			ARRAY_FILTER_USE_KEY
		);

		foreach ($route['types'] as $key => $type) {

			if (!array_key_exists($key, $params)) continue;
			if ($params[$key] === '') continue;

			if (!validate_type($type, $params[$key])) {

				if (Config::get('routing_log')) {
					nexi_debug_log([
						'result' => 'ERROR',
						'page'   => $page,
						'params' => $params,
						'error'  => 'INVALID_TYPE'
					]);
				}

				nexi_render_error_safe(
					nexi_lang('type_invalid_title'),
					nexi_lang('type_invalid_message', $key, $params[$key], $_GET['page'] ?? ''),
					422
				);
			}
		}

		$target = $route['target'];
		if (!str_contains($target, ':') && !str_starts_with($target, '/')) {
			$target = 'app:controller/' . ltrim($target, '/');
		}
		if (!str_ends_with($target, '.php')) {
			$target .= '.php';
		}

		$file = alias($target, false);

		if (!str_ends_with($file, '.controller.php')) {

			if (Config::get('routing_log')) {
				nexi_debug_log([
					'result' => 'ERROR',
					'page'   => $page,
					'file'   => $file,
					'error'  => 'INVALID_CONTROLLER'
				]);
			}

			nexi_render_error_safe(
				nexi_lang('controller_not_found_title'),
				nexi_lang('controller_not_found_message', path_relative($file)),
				500
			);
		}

		if (!file_exists($file)) {

			if (Config::get('routing_log')) {
				nexi_debug_log([
					'result' => 'ERROR',
					'page'   => $page,
					'file'   => $file,
					'error'  => 'CONTROLLER_NOT_FOUND'
				]);
			}

			nexi_render_error_safe(
				nexi_lang('not_found_title'),
				nexi_lang('not_found_message', path_relative($file)),
				404
			);
		}

		ctx::set('route.alias_map', $route['alias']);
		ctx::set('route.path', $route['original']);
		ctx::set('route.resolve', _link($route['original'], $params));
		ctx::set('route.real', path_relative($route['target']));
		ctx::set('route.params', $params);
		ctx::set('route.required', isset($route['required']) ? (bool) $route['required'] : false);
		ctx::set('route.query', $_GET);

		if (Config::get('routing_log')) {
			nexi_debug_log([
				'result'  => 'MATCHED',
				'page'    => $page,
				'pattern' => $route['original'],
				'regex'   => $route['pattern'],
				'file'    => $file,
				'params'  => $params,
			]);
		}

		require $file;

		self::run_after();
		return true;
	}

	if (Config::get('routing_log')) {
		nexi_debug_log([
			'result' => 'NO_MATCH',
			'page'   => $page,
			'error'  => 'NO_ROUTE_MATCHED'
		]);
	}

	return false;
}


	/**
	* Restituisce il path originale associato a un alias di rotta.
	*
	* Viene usato nel sistema di generazione dei link, tipicamente da _link() o helper simili:
	* getRoutePath() fornisce il pattern della rotta, che viene poi combinato con i parametri (slug, id, ecc.)
	* per produrre l’URL finale (/article/hello-world).
	* Non entra mai nel dispatch: serve solo per costruire URL coerenti con il routing dichiarativo.
	* Non esegue validazioni runtime.
	*
	* L'alias deve iniziare con "@" ed essere definito in routes.map.php.
	* Le rotte vengono lette dalla cache compilata (routes.cache.php)
	* e mantenute in memoria statica per evitare reload ripetuti.
	*
	* Esempio pratico:
	*
	* routes.map.php
	* '@article_show' => [
	*     'route'  => 'article/:slug',
	*     'target' => 'article/show'
	* ];
	*
	* Uso:
	* Route::getRoutePath('@article_show');
	*
	* Ritorna:
	* "article/:slug"
	*
	* Serve per generare URL partendo dall’alias, non per gestire richieste HTTP.

	* @param string $alias Alias della rotta (prefisso "@")
	* @return string|null Path originale della rotta se trovato, null altrimenti
	*/

	public static function getRoutePath(string $alias): ?string
	{
		// L'alias deve iniziare con @
		if (!str_starts_with($alias, '@')) {
			return null;
		}

		// Carica la cache se non ancora caricata
		static $cachedRoutes = null;
		if ($cachedRoutes === null) {
			$cachedRoutes = nexi_load_routes_map_cache();
		}

		// Cerca l'alias nella cache
		foreach ($cachedRoutes as $route) {
			if (isset($route['alias']) && $route['alias'] === $alias) {
				return $route['original'] ?? null;
			}
		}

		// Alias non trovato
		return null;
	}

}
/* ----------------- END ----------------- */

// ==================================================
// === [ROUTER] Internal Function Routing System ===
// === Nexipress for support routing
// ==================================================

	function is_debug(): bool
	{
		return Config::get('debug') === 'display';
	}

	/*
	* Carica la cache delle rotte mappa da file ottimizzato.
	* Se il file non esiste o è obsoleto, rigenera la cache.
	*
	* @return array Rotte dichiarative parse da usare in dispatch_map()
	*/
	function nexi_load_routes_map_cache(): array {

		// $siteId = defined('SITE_ID') ? SITE_ID : 'site-main';
		$cachePath = alias("approot:routes.cache.php",false);
		return file_exists($cachePath) ? require $cachePath : [];

	}
	/* ----------------- END ----------------- */

	/*
	* Valida un valore in base al tipo dichiarato in una rotta.
	*
	* Supporta i seguenti tipi:
	* - int            → solo cifre intere
	* - dbl            → numeri interi o decimali
	* - string         → qualunque stringa non numerica
	* - string-lower   → lettere minuscole e trattini (es. categoria-nuova)
	* - string-upper   → lettere maiuscole e trattini (es. ADMIN-PANEL)
	* - bool           → 'true' o 'false' (case-insensitive)
	* - slug           → lowercase, numeri e trattini (es. articolo-123)
	* - uuid           → UUID versione 4 standard (36 caratteri, con trattini)
	*
	* @param string $type Tipo dichiarato (es. 'int', 'slug', 'uuid')
	* @param string $val  Valore da validare
	* @return bool True se il valore è valido per il tipo specificato, false altrimenti
	*/
	function validate_type(string $type, string $val): bool {

		switch ($type) {
			case 'int':           return ctype_digit((string)$val);
			case 'dbl':           return is_numeric($val);
			case 'string':        return preg_match('/^(?!\d+$)[a-zA-Z0-9\-_]+$/', $val);
			case 'string-lower':  return preg_match('/^[a-z\-]+$/', $val);
			case 'string-upper':  return preg_match('/^[A-Z\-]+$/', $val);
			case 'bool':          return in_array(strtolower($val), ['true', 'false'], true);
			case 'slug':          return preg_match('/^[a-z0-9\-]+$/', $val);
			case 'uuid':          return preg_match('/^[a-f0-9\-]{36}$/i', $val);
			default:              return false;
		}

	}
	/* ----------------- END ----------------- */

	/*
	* Recupera e valida un parametro da query string o POST.
	*
	* - Se `$key` è null, restituisce l'intero array GET + POST (e lo salva in ctx).
	* - Se il parametro non esiste o è vuoto, restituisce -1.
	* - Se è definito un tipo, valida e converte il valore (int, float, bool, string, uuid, slug).
	* - Se sono definiti filtri, li applica in ordine (es: trim, lowercase, sanitize:email, etc).
	*
	* @param string|null $key     Nome del parametro da recuperare. Se null, restituisce tutti i parametri.
	* @param string|null $type    Tipo da forzare: 'int', 'float', 'bool', 'string', 'uuid', 'slug'.
	* @param string|null $filters Filtri opzionali separati da "|" (es: "trim|lowercase|sanitize:email").
	* @return mixed               Valore validato oppure -1 se fallisce la validazione.

	* Attenzione: la funzione restituisce -1 se il parametro non esiste o non è valido.
	* Evita di usare -1 come valore lecito nei parametri per non creare ambiguità.
	*/
	function param_query(?string $key = null, ?string $type = null, ?string $filters = null): mixed
	{
		static $merged = null;

		// Carica una sola volta tutti i parametri GET + POST e li salva nel contesto
		if ($merged === null) {
			$merged = array_merge($_GET, $_POST);
			ctx::set('query.params', $merged);
		}

		// Se non è richiesto un parametro specifico → restituisce tutti i parametri insieme
		if ($key === null) {
			return $merged;
		}

		// Recupera il valore richiesto
		$val = $merged[$key] ?? null;

		// Se mancante o vuoto, restituisce -1
		if ($val === null || $val === '') {
			return -1;
		}

		// Validazione e conversione per tipo esplicito
		switch ($type) {

			case 'int':
				if (!filter_var($val, FILTER_VALIDATE_INT)) return -1;
				$val = (int) $val;
				break;

			case 'float':
				$val = str_replace(',', '.', $val); // Normalizza separatore decimale
				if (!filter_var($val, FILTER_VALIDATE_FLOAT)) return -1;
				$val = (float) $val;
				break;

			case 'bool':
				$val = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($val === null) return -1;
				break;

			case 'string':
				// Solo alfanumerico url-safe (esclude numeri puri)
				if (!is_string($val) || is_numeric($val)) return -1;
				break;

			case 'uuid':
				if (!preg_match('/^[a-f0-9\-]{36}$/i', $val)) return -1;
				break;

			case 'slug':
				if (!preg_match('/^[a-z0-9\-]+$/', $val)) return -1;
				break;
		}

		// Applica eventuali filtri dichiarati (in ordine)
		if ($filters) {
			foreach (explode('|', $filters) as $filter) {
				if ($filter === 'trim') {
					$val = is_string($val) ? trim($val) : $val;

				} elseif ($filter === 'lowercase') {
					$val = is_string($val) ? strtolower($val) : $val;

				} elseif ($filter === 'uppercase') {
					$val = is_string($val) ? strtoupper($val) : $val;

				} elseif (str_starts_with($filter, 'sanitize:')) {
					$what = explode(':', $filter)[1];
					$flag = match ($what) {
						'email'  => FILTER_SANITIZE_EMAIL,
						'url'    => FILTER_SANITIZE_URL,
						'number' => FILTER_SANITIZE_NUMBER_INT,
						default  => null,
					};
					if ($flag) {
						$val = filter_var($val, $flag);
					}

				} elseif (str_starts_with($filter, 'validate:')) {
					$what = explode(':', $filter)[1];
					$flag = match ($what) {
						'email' => FILTER_VALIDATE_EMAIL,
						'url'   => FILTER_VALIDATE_URL,
						default => null,
					};
					if ($flag && !filter_var($val, $flag)) {
						return -1;
					}
				}
			}
		}

		return $val;
	}
	/* ----------------- END ----------------- */

	/*
	* Validazione di sicurezza su input
	* @param mixed $value
	* @return bool
	*/
	function is_param_safe(mixed $value): bool
	{
		$str = (string) $value;
		if (strlen($str) > 255) return false;
		if (preg_match('/[\x00-\x1F\x7F]/', $str)) return false;

		$patterns = [
			'/<script\b[^>]*>/i',
			'/\b(select|union|insert|update|delete|drop|sleep)\b/i',
			'/(--|#|;|\/\*|\*\/)/',
			'/\b(or|and)\b\s+\d+=\d+/i',
			'/\bjavascript:/i',
			'/\b(base64_decode|eval|exec|system|shell_exec)\b/i',
			'/\b(php:\/\/|file:\/\/|http:\/\/|https:\/\/)\b/i'
		];

		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $str)) return false;
		}
		return true;
	}
	/* ----------------- END ----------------- */