<?php
// ==================================================
// === [SYSTEM] Internal Function System ===
// === Nexipress core logic function e snippets
// ==================================================

// ====================================================
// === [HELPER] Funzioni helper shortcut ==============
// === Accesso rapido a alias, config, lang, ecc. =====
// === Usate in view, controller, snippet, fallback ===
// ====================================================

	/**
	* Include informazioni di debug ambiente se X_DEBUG è attivo.
	* No-op in produzione (mute).
	* ATTENZIONE: Arricchire con pi	u info ora che il core e maturo
	*/
	function nexi_debug_env(): void
	{
		if (X_DEBUG === 'mute') return;

		$path = alias('core:env.info.php', false);
		if ($path && is_file($path)) {
			include $path;
		}
	}

	/*
	* Resolve an alias into a real path or return the original key if not mapped.
	*
	* Behavior:
	* - Loads the alias map stored at runtime in the bootstrap (`alias.map` in context).
	* - If the alias contains a query string (e.g. "partials:np-footer.php?foo=bar"),
	*   it extracts variables into `$_GET` and strips the query part from the alias.
	* - Scans the map for a matching prefix (e.g. "thm_assets:js/file.js").
	*   When found, replaces it with the corresponding base path.
	* - Returns either:
	*   - A relative path (default, with `X_ROOT` replaced by "/"),
	*   - Or the absolute path if `$relative_path` is set to false.
	* - If no alias is found, simply returns the original key.
	*
	* Example:
	*   alias("thm_assets:js/app.js") → "/themes/mytheme/js/app.js"
	*
	* @param string|null $key Alias string with optional query part.
	* @param bool $relative_path  Whether to return a relative path (true) or absolute (false).
	* @return mixed Resolved path or the original key.
	*/
	function alias(?string $key = null, bool $relative_path = true): mixed
	{
		// Load alias map built at runtime from bootstrap
		$map = ctx::get('alias.map') ?? [];

		// Case 1: handle alias with query string (injects into $_GET)
		if (str_contains($key, '?')) {
			[$base, $query] = explode('?', $key, 2);
			if (isset($query)) {
				parse_str($query, $vars);
				$_GET = array_merge($_GET, $vars);
			}
			$key = $base; // keep only the alias part (e.g. "partials:np-footer.php")
		}

		// Case 2: resolve alias with prefix mapping (e.g. "thm_assets:js/file.js")
		foreach ($map as $prefix => $base) {
			if (str_starts_with($key, $prefix)) {

				// Extract relative part after the prefix
				$rel = substr($key, strlen($prefix));
				$path = $base . $rel;

				// Return relative or absolute path
				return $relative_path
					? str_replace(X_ROOT, '/', $path)
					: $path;
			}
		}

		// Case 3: no alias match - return original key
		return $key;
	}

	/* ----------------- END ----------------- */

	/* Restituisce il percorso assoluto associato a un alias simbolico. */

	function alias_include(string $key): string {
		return alias($key, false);
	}
	/* ----------------- END ----------------- */

	/*
	* Restituisce il path relativo a X_ROOT per evitare di esporre il path fisico.
	*
	* @param string $absolutePath
	* @return string
	*/
	function path_relative(string $absolutePath): string {
		return str_replace(X_ROOT, '', $absolutePath);
	}
	/* ----------------- END ----------------- */

	/**
	* Include una view del tema attivo risolvendo il path tramite alias.
	*
	* @param string $file Nome view (relativo, senza estensione).
	* @param array  $vars Variabili esposte alla view.
	*/
	function view(string $file, array $vars = []): void
	{
		// Sanitizzazione minima
		$file = trim($file, "/ \t\n\r\0\x0B");
		if ($file === '' || str_contains($file, '..')) {
			echo "<!-- Invalid view: {$file} -->";
			return;
		}

		// Risoluzione path (tema già gestito dall'alias)
		$path = alias("thm_pages:$file.php", false);

		if (!$path || !is_file($path)) {
			echo "<!-- View not found: {$file} -->";
			return;
		}

		if ($vars) {
			extract($vars, EXTR_SKIP);
		}

		require $path;
	}
	/* ----------------- END ----------------- */

	/*
	* Helper per accedere ai tipi supportati.
	*
	* - types()                 → restituisce tutti i tipi
	* - types('slug')           → restituisce 'slug' se esiste, altrimenti null
	* - types('slug', true)     → restituisce true/false
	*
	* Esempi d'uso
	* Check booleano (es. durante parse route)
	* if (!types('slug', true)) { tipo non supportato }
	*
	* Recupero tipo (es. normalizzazione)
	* $type = types('int'); // 'int' oppure null
	*
	* @param string|null $key
	* @param bool $checkOnly
	* @return mixed
	*/
	function types(?string $key = null, bool $checkOnly = false): mixed
	{
		$list = config::get('types');
		if (!is_array($list)) $list = [];

		if ($key === null) return $list;

		$exists = array_key_exists($key, $list);

		if ($checkOnly) return $exists;

		return $exists ? $key : null;
	}
	/* ----------------- END ----------------- */

	/*
	* Genera un URL a partire da un pattern con placeholder e parametri dinamici.
	*
	* Il pattern può contenere segnaposto nel formato `:nome`, eventualmente con tipo dichiarato (es. `:id(int)`).
	* I parametri possono essere passati in forma associativa o posizionale.
	*
	* Se il primo argomento è `'#self'`, il link viene costruito sulla base dell'URL attuale (`ctx('route.resolve')`)
	* con sostituzione dei segmenti finali da destra verso sinistra, in base ai parametri forniti.
	*
	* Se il primo argomento è `@alias`, viene risolta la rotta dichiarativa corrispondente tramite Route::getRoutePath().
	*
	* Comportamenti speciali:
	* - Se mancano parametri obbligatori, il segnaposto (`:nome`) rimane visibile nel risultato.
	* - Se vengono passati più parametri del necessario, quelli in eccesso vengono ignorati.
	* - I valori vengono codificati con `urlencode()`, compatibili con gli URL standard (`+` per gli spazi).
	*
	* Esempi d'uso
	* <a href="<?= _link('@articles_list', [ 'category' => 'tech', 'slug'     => 'nexipress-core' ]); ?>">test 1</a>
	* <a href="<?= _link('#self', ['pagina-2']); ?>">test 2</a>
	* <a href="<?= _link('admin/user/:id/edit', ['id' => 42]); ?>">test 3</a>
	*
	* @param string               $pattern Pattern con placeholder (es. 'admin/:id/:action'), `@alias`, o `'#self'`
	* @param array<string|int>|string $params Parametri da iniettare nel pattern (associativi o posizionali), oppure path già risolto
	* @return string URL generato, normalizzato, con `/` iniziale
	*/
	function _link(string $pattern, array|string $params = []): string
	{
		// Caso: path già risolto
		if (is_string($params)) {
			return '/' . ltrim($params, '/');
		}

		// Caso speciale: #self
		if ($pattern === '#self') {
			$current = trim((string)ctx::get('route.resolve'), '/');
			if ($current === '') return '/';

			$parts = explode('/', $current);
			$override = array_values($params);
			$len = count($parts);

			foreach ($override as $i => $value) {
				$idx = $len - count($override) + $i;
				if ($idx >= 0) {
					$parts[$idx] = rawurlencode((string)$value);
				}
			}

			return '/' . implode('/', $parts);
		}

		// Alias dichiarativo @alias
		if ($pattern[0] === '@') {
			$routePath = Route::getRoutePath($pattern);
			if (!$routePath) {
				return '/@INVALID_ROUTE';
			}
			$pattern = $routePath;
		}

		// Estrazione placeholder
		preg_match_all('/:([a-zA-Z0-9_]+)(\([^)]+\))?/', $pattern, $m);
		$placeholders = $m[1] ?? [];

		// Parametri posizionali → associativi
		if (array_is_list($params)) {
			$assoc = [];
			foreach ($placeholders as $i => $name) {
				$assoc[$name] = $params[$i] ?? null;
			}
			$params = $assoc;
		}

		// Sostituzione sicura
		foreach ($placeholders as $name) {
			if (array_key_exists($name, $params)) {
				$value = rawurlencode((string)$params[$name]);
			} else {
				// placeholder mancante → sempre visibile
				$value = ':' . $name;
			}

			$pattern = preg_replace(
				'/:' . $name . '(\([^)]+\))?/',
				$value,
				$pattern,
				1
			);
		}

		return '/' . ltrim($pattern, '/');
	}
	/* ----------------- END ----------------- */

	/*
	* Start a PHP session only if not already active.
	* Useful to avoid unnecessary sessions on pages that don't need them.
	*
	* @return void
	*/
	function session_lazy_start(): void {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}
	/* ----------------- END ----------------- */

	/*
	* Applica una trasformazione a un array associativo mantenendo le chiavi originali.
	* Esempio d'uso
	* $routes = [
	* 	'home' => 'home.controller',
	* 	'blog' => 'blog.controller',
	* ];

	* $resolved = array_map_assoc($routes, fn($target) => "app:$target");
	* [
	* 	'home' => 'app:home.controller',
	* 	'blog' => 'app:blog.controller',
	* ]
	*
	* @param array $array Array associativo da trasformare
	* @param callable $callback Funzione ($value, $key): mixed
	* @return array Nuovo array con le stesse chiavi trasformate
	*/
	function array_map_assoc(array $array, callable $callback): array
	{
		$result = [];

		foreach ($array as $key => $value) {
			$result[$key] = $callback($value, $key);
		}

		return $result;
	}
	/* ----------------- END ----------------- */

// ==================================================
// === [LINGUE] Funzioni helper shortcut ============
// === Accesso rapido a alias, config, lang, ecc. ===
// === Usate in view, controller, snippet, fallback ===
// ==================================================

	/*
	* Traslittera una stringa Unicode in ASCII leggibile usando una mappa per lingua.
	*
	* Questa funzione converte caratteri speciali o non latini (accentati, cirillici, vietnamiti, ecc.)
	* in lettere ASCII equivalenti, tramite una mappa caricata dinamicamente da file. Le mappe sono
	* modulari e salvate in: `application/locale/trans.{lang}.php`.
	*
	* @param string $text La stringa da translitterare (es. titolo, input utente, slug)
	* @param string|null $lang Codice lingua della mappa da usare (es. 'lat', 'ru', 'vi').
	*                          Se null, viene usata la mappa 'lat' di default.
	* @return string La stringa translitterata in ASCII.
	*
	* Esempi d'uso:
	*   nexi_transliterate("São Paulo è grande", 'lat'); // "Sao Paulo e grande"
	*   nexi_transliterate("Жёлтый человек", 'ru');       // "Zheltyi chelovek"
	*   nexi_transliterate("Đăng ký người dùng", 'vi');   // "Dang ky nguoi dung"
	*
	* Le mappe linguistiche devono restituire un array associativo:
	*   return ['á' => 'a', 'ß' => 'ss', 'Ж' => 'Zh', ...];
	*
	* NexiPress raccomanda:
	*   - URL: usare stringhe translitterate per compatibilità SEO
	*   - Contenuti a schermo: mantenere testo originale
	*   - Ricerca: normalizzare usando la stessa funzione
	*/
	function nexi_transliterate(string $text, ?string $lang = null): string {

		static $cache = [];
		$lang = $lang ?? 'lat'; // fallback automatico

		if ($lang !== null) {
			if (!isset($cache[$lang])) {
				$path = alias('lang:').'trans.' . $lang . '.php';
				if (is_file($path)) {
					$map = include $path;
					if (is_array($map)) {
						$cache[$lang] = $map;
					} else {
						$cache[$lang] = []; // fallback vuoto
					}
				} else {
					$cache[$lang] = []; // mappa mancante
				}
			}

			return strtr($text, $cache[$lang]);
		}

		// Se nessuna lingua, ritorna originale (oppure: strtr con mappa vuota)
		return $text;
	}
	/* ----------------- END ----------------- */

	/*
	* Restituisce una stringa localizzata dal file di lingua attivo.
	* ATTENZIONE: LINGUA DI SISTEMA NON RIGUARDA LA GESTIONE FRONTEND
	*
	* Ordine di priorità:
	* 1. $_SESSION['lang']
	* 2. $_COOKIE['lang']
	* 3. Fallback definito in config['lang.sys.fallback']
	*
	* Le stringhe vengono caricate da /system/locale/{lang}.php
	* e cache-izzate staticamente per prestazioni.
	*
	* Supporta sostituzione dinamica con vsprintf():
	*   nexi_lang('error.not_found', 'User')
	*   → "User not found"
	*
	* @param string $key   Chiave della stringa di lingua
	* @param mixed  ...$vars  Valori opzionali per la sostituzione nel messaggio
	* @return string        Stringa localizzata (o la chiave se non trovata)
	*/
	function nexi_lang(string $key, ...$vars): string {

		static $strings = []; $fallback = ctx::get('lang.sys.fallback');

		// $lang = $_SESSION['lang'] // lingua scelta dall’utente
		// 	?? $_COOKIE['lang'] // o da cookie
		// 	?? $fallback; // altrimenti fallback
		$lang = ctx::get('lang.sys.current') // o da ctx
			?? $fallback; // altrimenti fallback

		if (!isset($strings[$lang])) {
			$path = X_ROOT . "/system/locale/{$lang}.php";
			$strings[$lang] = file_exists($path) ? include $path : include X_ROOT . '/system/locale/'.$fallback.'.php';
		}

		$message = $strings[$lang][$key] ?? $key;

		if (!empty($vars)) {
			$message = vsprintf($message, $vars);
		}

		return $message;
	}
	/* ----------------- END ----------------- */

	/**
	* Helper per caricare una o più sezioni di lingua frontend.
	* Uso:
	* nexi_lang_front('ecommerce');
	* nexi_lang_front(['ecommerce', 'home']);
	*/
	function nexi_lang_front(string $locale, string|array $sections): void
	{
		// Automatic local
		if ($locale === null) {
			$locale = ctx::get('lang.current');
		}

		// Normalize in array
		if (is_string($sections)) {
			$sections = [$sections];
		}

		// Language file path
		$file = alias('lang:' . $locale . '.php', false);

		if (!file_exists($file)) {
			nexi_render_error(
				'Frontend language error',
				"Language file missing: {$locale}.php",
				500
			);
			return;
		}

		// Verify that the required sections exist
		$raw = require $file;

		foreach ($sections as $section) {
			if (!isset($raw[$section])) {
				nexi_render_error(
					'Frontend language error',
					"Section '{$section}' not found in locale '{$locale}'",
					500
				);
				return;
			}
		}

		// If everything is ok → delegate to the i18n system
		nexi_i18n::loadSections($locale, $sections);
	}
	/* ----------------- END ----------------- */

	/*
	* Rileva e restituisce la lingua attiva del sistema.
	*
	* Ordine:
	* - Se lang_mode è 'mono', ritorna il fallback
	* - Altrimenti: $_SESSION['locale'] → $_COOKIE['locale'] → fallback
	* - Valida che sia tra le lingue supportate
	*
	* @return string Codice lingua attiva (es: 'it', 'en')
	*/
	function nexi_locale_from_state(): string {

		if (Config::get('lang_mode') === 'mono') {
			return Config::get('lang_fallback');
		}

		$langs = Config::get('lang_locales');
		$lang_fallback = Config::get('lang_fallback');
		$locale = $_SESSION['locale'] ?? $_COOKIE['locale'] ?? null;

		if (!$locale) {
			$locale = $lang_fallback;
			$_SESSION['locale'] = $locale;
			setcookie('locale', $locale, time() + 86400 * 30, '/');
		}

		if (!in_array($locale, $langs, true)) {
			nexi_render_error(
				nexi_lang('language_unsupported_title'),
				nexi_lang('language_unsupported_desc', $locale),
				500
			);
		}

		return $locale;
	}
	/* ----------------- END ----------------- */

	/*
	* Inizializza la lingua corrente in base alla modalità:
	* - mono: nessuna gestione
	* - prefix: /it/pagina
	* - subdomain: it.miosito.com
	*
	* Supporta cambio via `?lang=xx` con redirect.
	*
	* @param string $url_active URL da cui analizzare lingua
	* @return void
	*/
	function nexi_locale_boot(string $url_active = ''): string {

		ctx::set('lang.mode', Config::get('lang_mode'));
		$mode     = Config::get('lang_mode');
		$langs    = Config::get('lang_locales');
		$fallback = Config::get('lang_fallback');
		$locale   = $fallback;

		// MONOLINGUA → salta tutto
		if ($mode === 'mono' || $mode === 'subdomain') {

			// MONO mode: rimuove eventuale prefix lingua dall'URL
			$path = trim(parse_url($url_active, PHP_URL_PATH) ?? '', '/');
			$segments = explode('/', $path);
			$first = $segments[0] ?? '';

			if (in_array($first, $langs, true)) { // Esiste una lingua nel URL?

				array_shift($segments); // Elimino la lingua (en/it/ecc.)
				$newPath = '/' . implode('/', $segments); // Ricostruisco il path
				if ($newPath === '') { $newPath = '/'; } // Se il path e vuoto vuol dire che punta alla home e normalizzo URL

				header('Location: ' . $newPath, true, 301);
				exit;
			}

			// Inserisco nel contesto la lingua Frontend
			ctx::set('lang.current', $fallback);
			ctx::set('lang.list', $langs);
			ctx::set('lang.fallback', $fallback);
			return $fallback;
		}

		// Rileva locale (cookie, sessione, ecc.)
		$detected = nexi_locale_from_state();
		if (is_string($detected) && in_array($detected, $langs, true)) {
			$locale = $detected;
		}

		ctx::set('lang.list', $langs);
		ctx::set('lang.fallback', $fallback);

		// Parsing URL
		$url_parts = parse_url($url_active);
		$path  = trim($url_parts['path'] ?? '', '/');
		$query = $url_parts['query'] ?? '';

		/*
		PREFIX:
		- URL con lingua → URL comanda
		- URL senza lingua → cookie decide
		- cookie/session = memoria, non autorità
		- una sola fonte di verità per request
		*/
		if ($mode === 'prefix') {

			$segments = $path !== '' ? explode('/', $path) : [];
			$first    = $segments[0] ?? '';

			// 1) URL con prefix lingua valido → vince l’URL
			if (in_array($first, $langs, true)) {

				$locale = $first;

				// Allinea sessione e cookie all’URL
				$_SESSION['locale'] = $locale;
				setcookie('locale', $locale, time() + 86400 * 30, '/');

				// Rimuove prefix lingua per il router
				$clean_path = '/' . implode('/', array_slice($segments, 1));
				if ($clean_path === '/') {
					$clean_path = '/';
				}

				$_SERVER['REQUEST_URI']     = $clean_path;
				$_SERVER['PATH_INFO']       = $clean_path;
				$GLOBALS['_NEXI_CLEAN_URI'] = $clean_path;
				$_GET['page']               = $clean_path;

			}
			// 2) URL senza prefix → redirect verso lingua attiva (cookie/fallback)
			else {

				$newPath = '/' . $locale;
				if ($path !== '') {
					$newPath .= '/' . $path;
				}
				if (!empty($query)) {
					$newPath .= '?' . $query;
				}

				header('Location: ' . $newPath, true, 301);
				exit;
			}
		}

		return $locale;
	}
	/* ----------------- END ----------------- */

// ==================================================
// === [NETWORK] Funzioni dedicate alla rete e gestione indirizzi ===
// === get info network
// ==================================================

	/*
	* Memo – Funzione futura: fn_resolve_ip_details()
	*
	* Funzione dedicata per ottenere informazioni avanzate su un indirizzo IP.
	* Interfaccia con API esterne (es. ipinfo, ip-api) o database interni per restituire:
	*
	* - Tipo IP (IPv4/IPv6)
	* - ASN e organizzazione
	* - Geolocalizzazione (paese, città)
	* - Indicatore rete condivisa (CGNAT, proxy, VPN)
	* - Flag residenziale/business/hosting
	*
	* Scopo: fornire dati affidabili per logging, analytics, limitazioni o sicurezza avanzata.
	*
	* Separata da fn_validate_identifier() per mantenere responsabilità distinte.
	*
	* Verifica se un indirizzo IP è contenuto in una lista di IP o intervalli CIDR.
	* @param array $allowed_ips Lista di IP singoli o intervalli in formato CIDR (es. 192.168.0.0/24).
	* @param string $ip Indirizzo IP da verificare.
	* @return bool True se l'IP è consentito, false altrimenti.
	*/
	function ip_in_allowed($allowed_ips, $ip) {

		foreach ($allowed_ips as $rule) {
			if (strpos($rule, '/') !== false) {
				// CIDR notation, match range
				if (ip_in_range($ip, $rule)) return true;
			} else {
				// IP singolo
				if ($ip === $rule) return true;
			}
		}
		return false;
	}
	/* ----------------- END ----------------- */

	/*
	* Verifica se un IP è compreso in un intervallo CIDR.
	* Supporta sia IPv4 che IPv6 tramite inet_pton.
	* @param string $ip   IP da verificare (es. "192.168.1.10").
	* @param string $cidr Intervallo in formato CIDR (es. "192.168.1.0/24").
	* @return bool True se l'IP rientra nell'intervallo, false altrimenti.
	*/
	function ip_in_range($ip, $cidr) {

		[$subnet, $mask] = explode('/', $cidr);
		$ip_bin = inet_pton($ip);
		$subnet_bin = inet_pton($subnet);

		if ($ip_bin === false || $subnet_bin === false) return false;

		$mask = (int)$mask;
		$len = strlen($ip_bin);
		$mask_bin = str_repeat("f", $mask >> 2);
		if ($mask % 4) {
			$mask_bin .= dechex((15 << (4 - ($mask % 4))) & 15);
		}
		$mask_bin = str_pad($mask_bin, $len * 2, '0');
		$mask_bin = pack("H*", $mask_bin);

		return ($ip_bin & $mask_bin) === ($subnet_bin & $mask_bin);
	}
	/* ----------------- END ----------------- */

	/*
	* Rileva l'indirizzo IP reale del client, compatibile con proxy e CDN.
	* @return string Indirizzo IP valido o '0.0.0.0' se non disponibile
	*/
	function ip_real(): string {

		foreach ([
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		] as $key) {
			if (!empty($_SERVER[$key])) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip);
					if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
				}
			}
		}
		return '0.0.0.0';
	}
	/* ----------------- END ----------------- */

	/*
	* Verifica se un indirizzo IP è in uno spazio privato (LAN, localhost, link-local).
	* Supporta IPv4 e IPv6.
	*
	* @param string $ip Indirizzo IP da analizzare
	* @return bool True se l'IP è privato, false se è pubblico
	*/
	function ip_is_private(string $ip): bool
	{
		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			return false;
		}

		// IPv4 ranges privati
		$private_ipv4 = [
			'10.0.0.0/8',
			'172.16.0.0/12',
			'192.168.0.0/16',
			'127.0.0.0/8',   // loopback
		];

		// IPv6 ranges privati
		$private_ipv6 = [
			'::1/128',       // loopback
			'fc00::/7',      // unique local address (ULA)
			'fe80::/10',     // link-local
		];

		foreach (array_merge($private_ipv4, $private_ipv6) as $range) {
			if (ip_in_range($ip, $range)) {
				return true;
			}
		}

		return false;
	}

	/*
	* Calcola l'intervallo effettivo (start-end) di un CIDR e verifica se l'IP è incluso.
	* Utile per strumenti di rete, debug o ACL.
	*
	* @param string $ip   IP da analizzare
	* @param string $cidr Intervallo CIDR (es. 192.168.1.0/24)
	* @return array|null [
	*     'in_range' => true|false,
	*     'start'    => 'IP iniziale del range',
	*     'end'      => 'IP finale del range',
	*     'mask'     => '255.255.255.0' (solo per IPv4),
	* ]
	*/
	function ip_mask(string $ip, string $cidr): ?array
	{
		[$subnet, $mask] = explode('/', $cidr);
		$ip_bin = inet_pton($ip);
		$subnet_bin = inet_pton($subnet);

		if ($ip_bin === false || $subnet_bin === false) return null;

		$mask = (int)$mask;
		$len = strlen($ip_bin);

		$mask_bin = str_repeat("f", $mask >> 2);
		if ($mask % 4) {
			$mask_bin .= dechex((15 << (4 - ($mask % 4))) & 15);
		}
		$mask_bin = str_pad($mask_bin, $len * 2, '0');
		$mask_bytes = pack("H*", $mask_bin);

		$start = $subnet_bin & $mask_bytes;
		$end   = $subnet_bin | (~$mask_bytes);

		return [
			'in_range' => ($ip_bin >= $start && $ip_bin <= $end),
			'start'    => inet_ntop($start),
			'end'      => inet_ntop($end),
			'subnet_mask'     => ($len === 4) ? long2ip((ip2long('255.255.255.255') << (32 - $mask)) & 0xFFFFFFFF) : null
		];
	}

// ==================================================
// === [SECURITY] Funzioni dedicate alla sicurezza e criptazione ===
// === Nexipress core logic, loader, sicurezza, debug
// ==================================================

	/*
	* Start a hardened PHP session on demand, safely and with sane defaults.
	* - Idempotent: no warnings if already active
	* - Secure cookies: SameSite=Lax, HttpOnly, Secure (if HTTPS)
	* - Stable domain: from config or base registrable domain (no port)
	* - CSRF token: generated once per fresh session
	* - Fingerprint: tolerant (binds to User-Agent; IP only soft-check)
	*/
	function fn_request_session_start(): void
	{
		// Already active → nothing to do
		if (session_status() === PHP_SESSION_ACTIVE) return;

		// ---- Environment & cookie target ---------------------------------------
		$session_name  = 'nexi_sid';
		$https         = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
		$ua            = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$ip            = $_SERVER['REMOTE_ADDR'] ?? '';
		$host_raw      = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$host_no_port  = preg_replace('/:\d+$/', '', $host_raw);

		// Prefer explicit domain from config; fallback to base domain (example.com)
		$cfg_domain    = Config::get('cookie_domain') ?? null;
		if ($cfg_domain) {
			$cookie_domain = $cfg_domain;
		} else {
			// Reduce subdomain issues: keep registrable domain if possible
			if (filter_var($host_no_port, FILTER_VALIDATE_IP)) {
				$cookie_domain = $host_no_port; // IP hosts must be exact
			} else {
				$parts = explode('.', $host_no_port);
				$cookie_domain = (count($parts) >= 2) ? implode('.', array_slice($parts, -2)) : $host_no_port;
			}
		}

		// ---- INI hardening (cookie-only, no URL-based IDs) ---------------------
		ini_set('session.use_strict_mode', '1');
		ini_set('session.use_cookies', '1');
		ini_set('session.use_only_cookies', '1');
		ini_set('session.cookie_httponly', '1');
		ini_set('session.cookie_secure', $https ? '1' : '0');
		ini_set('session.cookie_samesite', 'Lax');

		// ---- Cookie params (must be before session_start) ----------------------
		$cookieParams = [
			'lifetime' => 0,
			'path'     => '/',
			'domain'   => $cookie_domain,
			'secure'   => $https,
			'httponly' => true,
			'samesite' => 'Lax',
		];
		session_set_cookie_params($cookieParams);
		session_name($session_name);

		// ---- Start session -----------------------------------------------------
		session_start();

		// ---- First-time setup: CSRF + rotate ID -------------------------------
		if (empty($_SESSION['__init'])) {

			// CSRF token (random; encrypt if helper available)
			$raw = base64_encode(random_bytes(32));
			$_SESSION['csrf_token'] = function_exists('fn_encrypt') && defined('KEY_STT')
				? fn_encrypt($raw, KEY_STT)
				: $raw;

			// Store fingerprint (UA + soft IP prefix)
			$ip_soft = strpos($ip, ':') !== false ? $ip : preg_replace('/\.\d+$/', '.0', $ip); // IPv4 /24-ish
			$_SESSION['fp'] = hash('sha256', $ua . '|' . $ip_soft);

			$_SESSION['__init'] = time();
			session_regenerate_id(true); // mitigate fixation
		} else {

			// ---- Tolerant fingerprint check ------------------------------------
			$ip_soft = strpos($ip, ':') !== false ? $ip : preg_replace('/\.\d+$/', '.0', $ip);
			$current = hash('sha256', $ua . '|' . $ip_soft);

			// If UA changes drastically, rotate ID & refresh fingerprint (don’t break UX)
			if (!hash_equals($_SESSION['fp'] ?? '', $current)) {
				$_SESSION['fp'] = $current;
				session_regenerate_id(true);
				// Optional: u.log('session:fingerprint-rotated', ['ip'=>$ip,'ua'=>$ua]);
			}
		}

		// ---- Publish session id -----------------------------------------------
		if (!defined('APP_SID')) define('APP_SID', session_id());
	}

	/*
	* Carica e valida le chiavi di sicurezza da secure.keys.php.
	* Se il file è assente o corrotto, lo rigenera automaticamente con chiavi URL-safe.
	* Ripristina i permessi, owner e gruppo coerenti con la cartella /system.
	*
	* @return array Associativo con tutte le chiavi richieste (es. KEY_STT, KEY_API)
	* @throws void Se impossibile scrivere o ripristinare il file, genera errore 412.
	*/
	function fn_load_secure_keys(): array {

		$file = NP_ROOT . '/system/secure.key.php';
		$dir  = dirname($file);

		// Chiavi richieste
		$required = ['KEY_STT', 'KEY_API'];

		// Valida array e lunghezza
		$validate = function ($data) use ($required): bool {
			if (!is_array($data)) return false;
			foreach ($required as $key) {
				if (!isset($data[$key]) || !is_string($data[$key]) || strlen($data[$key]) !== 32) {
					return false;
				}
			}
			return true;
		};

		// Se esiste ed è valido → ritorna
		if (is_file($file)) {
			$data = @include $file;
			if ($validate($data)) return $data;
		}

		// Funzione per chiave URL-safe
		$make_key = fn() => str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes(24)));

		// Genera nuove chiavi
		$generated = [];
		foreach ($required as $key) {
			$generated[$key] = $make_key();
		}

		// Prepara contenuto del file
		$content = "<?php\nreturn " . var_export($generated, true) . ";\n";

		// Permessi originari
		$perm_orig = @fileperms($dir);
		$writable  = is_writable($dir);

		if (!$writable && $perm_orig !== false) {
			@chmod($dir, 0755); // prova a rendere scrivibile
		}

		$success = @file_put_contents($file, $content);

		// Ripristina permessi originari se modificati
		if ($perm_orig !== false) {
			@chmod($dir, $perm_orig & 07777);
		}

		if ($success === false) {
			nexi_render_error(
				"Precondizione fallita",
				"Impossibile scrivere secure.keys.php: controlla i permessi della cartella /system.",
				412
			);
		}

		// Copia permessi, owner e gruppo dalla cartella al file
		$stat = @stat($dir);
		if ($stat) {
			@chmod($file, $stat['mode'] & 07777);
			@chown($file, $stat['uid']);
			@chgrp($file, $stat['gid']);
		}

		return $generated;
	}
	/* ----------------- END ----------------- */

	/*
	* Cifra una stringa in modo sicuro utilizzando AES-256-CBC.
	* Combina IV e testo cifrato, restituito in formato base64 URL-safe.
	*
	* @param string $fn_plaintext Testo da cifrare.
	* @param string $fn_key Chiave di cifratura a 32 byte.
	* @return string|false Stringa cifrata (base64 URL-safe) o false in caso di errore.
	*/
	function fn_encrypt(string $fn_plaintext, string $fn_key): string|false {

		// Genera un vettore di inizializzazione (IV) casuale.
		$iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

		// Cifra il testo in chiaro.
		$ciphertext = openssl_encrypt($fn_plaintext, 'aes-256-cbc', $fn_key, OPENSSL_RAW_DATA, $iv);

		// Se la crittografia fallisce, torna false.
		if ($ciphertext === false) {
			return false;
		}

		// Combina IV e testo cifrato per la decodifica.
		$ciphertext_combined = $iv . $ciphertext;

		// Restituisce la stringa cifrata in base64.
		return base64_encode($ciphertext_combined);
	}
	/* ----------------- END ----------------- */

	/*
	* Decifra una stringa precedentemente cifrata con AES-256-CBC.
	* Richiede la chiave originale e il formato base64 generato da fn_encrypt().
	*
	* @param string $ciphertext_base64 Testo cifrato (base64 URL-safe).
	* @param string $fn_key Chiave di decifratura a 32 byte.
	* @return string|false Testo decifrato o false in caso di errore o formato invalido.
	*/
	function fn_decrypt(string $ciphertext_base64, string $fn_key): string|false {

		// Decodifica il testo cifrato da base64.
		$ciphertext_combined = base64_decode($ciphertext_base64);

		// Se la decodifica base64 fallisce, torna false.
		if ($ciphertext_combined === false) {
			return false;
		}

		// Estrai il vettore di inizializzazione (IV).
		$iv_length = openssl_cipher_iv_length('aes-256-cbc');

		// Verifica che il testo cifrato abbia una lunghezza valida, altrimenti torna false.
		if (strlen($ciphertext_combined) < $iv_length) {
			return false;
		}

		$iv = substr($ciphertext_combined, 0, $iv_length);

		// Estrai il testo cifrato vero e proprio.
		$ciphertext = substr($ciphertext_combined, $iv_length);

		// Decifra il testo cifrato.
		$plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $fn_key, OPENSSL_RAW_DATA, $iv);

		// Se la decrittografia fallisce, torna false.
		if ($plaintext === false) {
			return false;
		}

		// Restituisce il testo in chiaro.
		return $plaintext;
	}
	/* ----------------- END ----------------- */

	/*
	* Genera un identificatore univoco breve (fingerprint) a partire da un valore opzionale.
	* Serve a generare un "impronta" unica, non ripetibile
	* Fingerprint = identificatore breve, casuale, univoco, non prevedibile
	*
	* @param string|null $identifier Chiave opzionale per influenzare il risultato
	* @param int $length Lunghezza del risultato finale (default 10)
	* @return string Fingerprint univoco e URL-safe
	*/
	function fn_generate_fingerprint(?string $identifier = null, int $length = 32): string {
		$base = bin2hex(random_bytes(16)) . ($identifier ?: 'unknown') . microtime(true);
		return str_replace(['+', '/', '='], ['-', '_', ''], substr(base64_encode(hash('sha256', $base, true)), 0, $length));
	}
	/* ----------------- END ----------------- */

	/*
	* Valida un indirizzo IP (IPv4 o IPv6) e genera un identificatore deterministico.
	*
	* La funzione può essere utilizzata in due modalità:
	* - Validazione semplice: verifica se l'input è un IP valido
	* - Generazione identificatore: costruisce un hash deterministico e stabile in base all'IP e all'utente
	*
	* L'identificatore è pensato per usi applicativi interni (es. cache, tracciamento anonimo, chiavi Redis),
	* ed è costruito combinando il tipo di IP, l'indirizzo stesso, un prefisso e l'UID persistente dell'utente.
	*
	* In caso di IP non valido e se $isIdentifier è true, viene generato un fallback basato su fingerprint interno.
	*
	* L'identificatore è sicuro e stabile per uso applicativo, ma non adatto a scopi crittografici o di autenticazione.
	*
	* @param string|null $input         L'indirizzo IP da validare o identificare
	* @param bool        $isIdentifier  Se true, forza la generazione dell'identificatore anche in fallback
	*
	* @return array{
	*     ip-return: bool,
	*     base?: string,
	*     type: 'IPv4'|'IPv6'|'FINGERPRINT'|null,
	*     identifier: string|null
	* }
	*/
	function fn_validate_identifier(?string $input = '', bool $isIdentifier = true): array
	{
		$input = trim((string) $input);

		// Valido IPv4
		if (filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			if ($isIdentifier) {

				$type = 'IPv4';
				$base = Config::get("prefix_short") . '::' . strtoupper($type) . '::' . trim($input) . '::' . fn_get_or_create_uid();
				$identifier = hash('sha256', $base);
				return [
					'ip-return'  => true,
					'base'       => $base,
					'type'       => $type,
					'identifier' => hash('sha256', 'IPV4::' . $base)
				];
			}
		}

		// Valido IPv6
		if (filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			if ($isIdentifier) {
				$type = 'IPv6';
				$base = Config::get("prefix_short") . '::' . strtoupper($type) . '::' . trim($input) . '::' . fn_get_or_create_uid();
				$identifier = hash('sha256', $base);
				return [
					'ip-return'  => true,
					'base'       => $base,
					'type'       => $type,
					'identifier' => hash('sha256', 'IPV6::' . $base)
				];

			}
		}

		// Nessun IP valido → fallback solo se richiesto identificatore
		if ($isIdentifier) {

			$type = 'FINGERPRINT';
			$base = Config::get("prefix_short") . '::' . strtoupper($type) . '::' . fn_get_or_create_uid();

			return [
				'ip-return'  => false,
				'base'       => $base,
				'type'       => $type,
				'identifier' => hash('sha256', $base)
			];
		}

		// Nessun IP valido e no identificatore richiesto
		return [
			'ip-return'  => false,
			'type'       => null,
			'identifier' => null
		];
	}
	/* ----------------- END ----------------- */

	/*
	* Genera un hash HMAC-SHA256 a partire da una stringa e una chiave segreta.
	* @param string $input
	* @param string $secret
	* @return string Hash generato
	*/
	function fn_create_hash(string $input, string $secret): string {
		return hash_hmac('sha256', $input, $secret);
	}
	/* ----------------- END ----------------- */

	/*
	* Verifica se un hash corrisponde alla stringa data con chiave.
	* @param string $input
	* @param string $secret
	* @param string $hash Hash da confrontare
	* @return bool True se valido
	*/
	function fn_verify_hash(string $input, string $secret, string $hash): bool {
		return hash_equals(fn_create_hash($input, $secret), $hash);
	}
	/* ----------------- END ----------------- */

	/*
	* Verifica che il token fornito corrisponda a quello salvato nella sessione utente.
	* @param string $token Token CSRF da validare
	* @return bool True se il token è valido, false altrimenti
	*/
	function fn_validate_csrf_token(string $token): bool {
		return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
	}
	/* ----------------- END ----------------- */

	/*
	* Verifica se una stringa corrisponde a un hash SHA-256 valido (64 caratteri esadecimali).
	* @param string $hash Stringa da verificare
	* @return bool True se è un hash SHA-256 valido, false altrimenti
	*/
	function fn_is_sha256(string $hash): bool {
		return preg_match('/^[a-f0-9]{64}$/i', $hash) === 1;
	}
	/* ----------------- END ----------------- */

// ==================================================
// === [STRING] Funzioni dedicate alla manipolazione delle stringhe ===
// === Manipolazione delle stringhe
// ==================================================

	/*
	* Converte una stringa Unicode in formato ASCII utilizzabile in vari contesti (slug, normalizzazione, case).
	*
	* La funzione applica traslitterazione in base alla lingua specificata, rimuove caratteri non ASCII
	* e restituisce la stringa secondo il comportamento selezionato.
	*
	* @param string $text La stringa di input da elaborare
	* @param string $behavior Comportamento richiesto:
	*     - 'slug'  → restituisce uno slug SEO-friendly (es. "citta-di-sao-paulo")
	*     - 'clean' → restituisce una versione pulita e leggibile (es. "Citta di Sao Paulo")
	*     - 'case'  → restituisce la stringa traslitterata e convertita in un formato case (snake, camel...)
	* @param string $lang Codice lingua da usare per la traslitterazione (default: 'lat')
	* @param string|null $caseFormat Formato case da usare se behavior = 'case' (es. 'snake', 'kebab', 'camel', ...)
	* @return string La stringa elaborata in base al comportamento scelto.
	*
	* Se la stringa risultante è vuota ma l’originale non lo era, restituisce '[unconvertible-text]' come fallback.
	*
	* Esempi d'uso:
	*   fn_str_ascii("São Paulo è grande", 'slug', 'lat');       // "sao-paulo-e-grande"
	*   fn_str_ascii("Đăng ký người dùng", 'clean', 'vi');       // "Dang ky nguoi dung"
	*   fn_str_ascii("Жёлтый человек", 'case', 'ru', 'snake');   // "zheltyi_chelovek"
	*
	* La traslitterazione avviene tramite nexi_transliterate(), che carica le mappe da:
	*   application/locale/trans.{lang}.php
	*
	* NexiPress raccomanda:
	*   - 'slug' per URL e permalink
	*   - 'clean' per output leggibile ma senza accenti
	*   - 'case' per normalizzazioni interne, nomi macchina o identificatori coerenti
	*/
	function fn_str_ascii(string $text, string $behavior = 'slug', string $lang= 'lat', ?string $caseFormat = null): string {

		$original = $text;
		$text = nexi_transliterate($text, $lang);
		$text = preg_replace('/[^\x20-\x7E]/', '', $text); // rimuove residui non ASCII

		// Fallback: se il risultato è vuoto ma l'originale non lo era
		if (trim($text) === '' && trim($original) !== '') {
			return '[unconvertible-text]';
		}

		switch ($behavior) {
			case 'clean':
				$text = preg_replace('/[\x00-\x1F\x7F\xA0]+/u', '', $text);
				$text = preg_replace('/[\p{C}]/u', '', $text);
				return trim($text);
			case 'slug':
				return fn_str_slug($text);
			case 'case':
				if (!$caseFormat) return $text;
				return fn_str_convert_case($text, $caseFormat);
			default:
				return $text;
		}
		if (trim($text) === '' && trim($original) !== '') {
			return '[unconvertible-text]'; // oppure throw, oppure log
		}
	}

	/*
	* Aggiunge caratteri a sinistra, destra o entrambi i lati di una stringa.
	*
	* @param string $text Stringa originale
	* @param int $count Numero di caratteri da aggiungere (default 1)
	* @param string $char Carattere o stringa da usare come padding (default: spazio)
	* @param string $side Lato su cui aggiungere: 'left', 'right', 'both'
	* @return string Stringa con padding applicato
	* Utile a chi deve:
	* - formattare stringhe visivamente (UI, CLI, badge)
	* - generare output leggibili (es. log, email testuali, template fissi)
	* - comporre stringhe con struttura (codici, placeholder, evidenziazione)
	*/
	function fn_str_pad_side(string $text, int $count = 1, string $char = ' ', string $side = 'right'): string {
		$pad = str_repeat($char, $count);

		return match ($side) {
			'left' => $pad . $text,
			'right' => $text . $pad,
			'both' => $pad . $text . $pad,
			default => $text
		};
	}

	/*
	* Tronca una stringa semplice o HTML preservando la struttura e tagliando in base a delimitatori.
	*
	* @param string $string Testo da troncare
	* @param int $maxLength Numero massimo di caratteri
	* @param string $suffix Suffix da aggiungere (es. '...')
	* @param array $delimiters Delimitatori preferiti per il taglio (default: spazio)
	* @return string Stringa troncata, con HTML preservato se presente
	*/
	function fn_cut_string(string $string, int $maxLength = 50, string $suffix = '...', array $delimiters = [' '] ): string {

		// Caso testo semplice (senza HTML)
		if (strip_tags($string) === $string) {
			if (mb_strlen($string) <= $maxLength) return $string;

			$cut = mb_substr($string, 0, $maxLength);
			$lastPos = false;
			foreach ($delimiters as $d) {
				$pos = mb_strrpos($cut, $d);
				if ($pos !== false && ($lastPos === false || $pos > $lastPos)) {
					$lastPos = $pos;
				}
			}
			if ($lastPos !== false) $cut = mb_substr($cut, 0, $lastPos);
			return $cut . $suffix;
		}

		// Caso HTML
		$dom = new DOMDocument();
		@$dom->loadHTML(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'));

		$truncated = '';
		$total = 0;
		$body = $dom->getElementsByTagName('body')->item(0);
		if (!$body) return '';

		foreach ($body->childNodes as $node) {
			if ($node instanceof DOMText) {
				$length = mb_strlen($node->nodeValue);
				if ($total + $length > $maxLength) {
					$cutText = mb_substr($node->nodeValue, 0, $maxLength - $total);
					$lastPos = false;
					foreach ($delimiters as $d) {
						$pos = mb_strrpos($cutText, $d);
						if ($pos !== false && ($lastPos === false || $pos > $lastPos)) {
							$lastPos = $pos;
						}
					}
					if ($lastPos !== false) $cutText = mb_substr($cutText, 0, $lastPos);
					$truncated .= htmlspecialchars($cutText) . $suffix;
					break;
				} else {
					$truncated .= htmlspecialchars($node->nodeValue);
					$total += $length;
				}
			} else {
				$truncated .= $dom->saveHTML($node);
			}
		}

		return mb_convert_encoding($truncated, 'UTF-8', 'HTML-ENTITIES');
	}
	/* ----------------- END ----------------- */

	/*
	* Genera una stringa casuale con caratteri specifici, sicura per uso generico.
	*
	* @param int $typeString Tipo di set caratteri da usare:
	*  1 = lettere maiuscole/minuscole + numeri
	*  2 = solo numeri
	*  3 = solo lettere minuscole
	*  4 = solo lettere maiuscole
	*  5 = numeri + lettere minuscole
	*  6 = numeri + lettere maiuscole
	*  7 = lettere maiuscole + minuscole
	* @param int $strLength Lunghezza della stringa da generare (default 6)
	* @return string Stringa casuale generata
	*/
	function fn_random_string(int $typeString=1, int $strLength = 6): string {

		$validCharacters = match($typeString) {
			1 => 'abcdefghijklmnopqrstuxyvwz0123456789ABCDEFGHIJKLMNOPQRSTUXYVWZ',
			2 => '1234567890',
			3 => 'abcdefghijklmnopqrstuxyvwz',
			4 => 'ABCDEFGHIJKLMNOPQRSTUXYVWZ',
			5 => '1234567890abcdefghijklmnopqrstuxyvwz',
			6 => '1234567890ABCDEFGHIJKLMNOPQRSTUXYVWZ',
			7 => 'abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ',
			default => 'abcdefghijklmnopqrstuxyvwz0123456789ABCDEFGHIJKLMNOPQRSTUXYVWZ'
		};

		$validCharactersArray = str_split($validCharacters);
		$result = '';
		$maxIndex = count($validCharactersArray) - 1;

		for ($i = 0; $i < $strLength; $i++) {
			$index = random_int(0, $maxIndex);
			$result .= $validCharactersArray[$index];
		}

		return $result;
	}
	/* ----------------- END ----------------- */

	/*
	* Tronca una stringa al numero massimo di parole specificato.
	*
	* @param string $text Testo in ingresso
	* @param int $max Numero massimo di parole (default 10)
	* @return string Stringa troncata
	*/
	function fn_str_limit_words(string $text, int $max = 10): string {
		$words = preg_split('/\s+/', trim($text));
		if (count($words) <= $max) return $text;
		return implode(' ', array_slice($words, 0, $max)) . '...';
	}
	/* ----------------- END ----------------- */

	/*
	* Rimuove caratteri non stampabili, invisibili o unicode non standard da una stringa.
	*
	* @param string $text Testo da ripulire
	* @return string Testo pulito
	*/
	function fn_str_clean(string $text): string {
		$text = preg_replace('/[\x00-\x1F\x7F\xA0]+/u', '', $text); // invisibili
		$text = preg_replace('/[\p{C}]/u', '', $text); // unicode non stampabili
		return trim($text);
	}
	/* ----------------- END ----------------- */

	/*
	* Converte una stringa in uno dei seguenti formati: snake_case, kebab-case, camelCase, PascalCase,
	* UPPER_SNAKE_CASE e SCREAMING-KEBAB.
	*
	* @param string $text Testo da convertire (es. 'Titolo principale grande')
	* @param string $format Formato di destinazione: 'snake', 'kebab', 'camel', 'pascal', 'upper_snake', 'screaming_kebab'
	* @return string Testo convertito nel formato richiesto
	*/
	function fn_str_convert_case(string $text, string $format = 'snake'): string {

		// Pulizia base
		$text = strip_tags($text);
		$text = preg_replace('/[^\p{L}\p{Nd}]+/u', ' ', $text); // Rimuove simboli e sostituisce con spazio
		$text = mb_strtolower(trim($text));

		$words = preg_split('/\s+/', $text);

		switch ($format) {
			case 'snake':
				return implode('_', $words);
			case 'kebab':
				return implode('-', $words);
			case 'camel':
				$first = array_shift($words);
				$rest = array_map('ucfirst', $words);
				return $first . implode('', $rest);
			case 'pascal':
				$rest = array_map('ucfirst', $words);
				return implode('', $rest);
			case 'upper_snake':
				return mb_strtoupper(implode('_', $words));
			case 'screaming_kebab':
				return mb_strtoupper(implode('-', $words));
			default:
				return $text; // fallback: ritorna minuscola con spazi
		}
	}
	/* ----------------- END ----------------- */

	/*
	* Estrae un frammento leggibile di testo attorno a una parola o frase chiave, mantenendo solo parole intere.
	*
	* Cerca la parola nel testo, estrae un numero configurabile di parole prima e dopo,
	* e restituisce un contesto coerente, evitando troncamenti nel mezzo delle parole.
	* Se la frase non è trovata, restituisce un estratto iniziale del testo come fallback.
	* Aggiunge i puntini "..." se il frammento è troncato rispetto al testo originale.
	*
	* Utile per:
	* - Motori di ricerca interni
	* - Moduli di ricerca globale
	* - Plugin per evidenziare keyword
	* - Componenti anteprima contenuti (es. blog, commenti, documentazione)
	*
	* Si usa quando serve:
	* - Mostrare contesto attorno a una parola chiave
	* - Generare anteprime di testo focalizzate su una query
	* - Restituire snippet nei risultati di ricerca
	* - Evidenziare match in un elenco di risultati
	* - Risposte da ricerche AJAX o suggerimenti istantanei
	*
	* @param string $text Testo completo da cui estrarre
	* @param string $phrase Parola o frase da cercare
	* @param int $wordRadius Numero di parole da includere prima e dopo (default: 5)
	* @param string $suffix Suffix da usare se il testo viene troncato (default: "...")
	* @return string Frammento centrato sulla frase chiave, leggibile e coerente
	*/
	function fn_str_excerpt(string $text, string $phrase, int $wordRadius = 5, string $suffix = '...'): string {

		$text = strip_tags($text);
		$phrase = trim($phrase);

		if ($phrase === '') return fn_cut_string($text, 100, $suffix);

		$words = preg_split('/\s+/u', $text);
		$phraseWords = preg_split('/\s+/u', $phrase);
		$phraseLength = count($phraseWords);

		// Trova l'indice della prima parola che matcha
		$matchIndex = -1;
		$total = count($words);
		for ($i = 0; $i <= $total - $phraseLength; $i++) {
			$segment = array_slice($words, $i, $phraseLength);
			if (mb_strtolower(implode(' ', $segment)) === mb_strtolower($phrase)) {
				$matchIndex = $i;
				break;
			}
		}

		if ($matchIndex === -1) {
			// Frase non trovata, fallback
			return fn_cut_string($text, 100, $suffix);
		}

		// Costruisci contesto attorno
		$start = max(0, $matchIndex - $wordRadius);
		$end = min($total, $matchIndex + $phraseLength + $wordRadius);
		$excerpt = implode(' ', array_slice($words, $start, $end - $start));

		if ($start > 0) $excerpt = $suffix . ' ' . ltrim($excerpt);
		if ($end < $total) $excerpt = rtrim($excerpt) . ' ' . $suffix;

		return $excerpt;
	}
	/* ----------------- END ----------------- */

	/*
	* Evidenzia porzioni di testo tramite parole chiave o posizione, con supporto a vari stili di output.
	*
	* Supporta due modalità:
	* - **Keyword**: evidenzia una o più parole/frasi all'interno del testo
	* - **Range**: evidenzia un intervallo noto (start-end) espandendolo ai bordi parola
	*
	* Lo stile di evidenziazione può essere configurato:
	* - `mark`: usa il tag <mark> (evidenziatore)
	* - `underline`: sottolineatura con <span class="np-underline">
	* - `both`: evidenziazione + sottolineatura (es. <mark class="np-underline">)
	*
	* @param string $text Il testo completo da analizzare
	* @param string|array|null $keywords Una o più parole/frasi da evidenziare (ignora se usi $start/$end)
	* @param string $style Tipo di evidenziazione: 'mark' (default), 'underline', 'both'
	* @param bool $caseSensitive Se true, distingue maiuscole/minuscole
	* @param ?int $start Posizione iniziale (carattere) da evidenziare (opzionale)
	* @param ?int $end Posizione finale (esclusiva) da evidenziare (opzionale)
	* @return string Testo HTML-safe con evidenziazione applicata
	*
	* $text = "NexiPress è un CMS potente e moderno pensato Per sviluppatori moderni.";
	* @example fn_str_highlight($text, 'CMS');
	* @example fn_str_highlight($text, ['NexiPress', 'moderno'], 'both');
	* @example fn_str_highlight($text, null, 'mark', false, 15, 30);
	*
	* @note Se vengono passati sia $keywords che $start/$end, ha priorità la modalità *range*.
	* @note L'intervallo viene espanso per non troncare le parole.
	*/

	function fn_str_highlight(
		string $text,
		string|array|null $keywords = null,
		string $style = 'mark',
		bool $caseSensitive = false,
		?int $start = null,
		?int $end = null
	): string {

		// Se $start e $end sono forniti, evidenzia range (senza tagliare parole)
		if ($start !== null && $end !== null && $start < $end) {
			$before = mb_substr($text, 0, $start);
			$highlight = mb_substr($text, $start, $end - $start);
			$after = mb_substr($text, $end);

			// Aggiustamento: espandi il range ai bordi parola
			preg_match('/^(\S*)/', $after, $right);
			preg_match('/(\S*)$/u', $before, $left);

			$highlight = (isset($left[1]) ? $left[1] : '') . $highlight . (isset($right[1]) ? $right[1] : '');
			$before = mb_substr($before, 0, mb_strlen($before) - mb_strlen($left[1] ?? ''));
			$after = mb_substr($after, mb_strlen($right[1] ?? ''));

			return $before . fn_wrap_highlight($highlight, $style) . $after;
		}

		// Altrimenti, evidenzia parole/frasi
		if (empty($keywords)) return $text;

		$keywords = is_array($keywords) ? $keywords : [$keywords];
		$escaped = array_map('preg_quote', $keywords);
		$pattern = '/\b(' . implode('|', $escaped) . ')\b/u' . ($caseSensitive ? '' : 'i');

		return preg_replace_callback($pattern, function ($match) use ($style) {
			return fn_wrap_highlight($match[0], $style);
		}, $text);
	}
	/* ----------------- END ----------------- */

	/*
	* Applica lo stile di evidenziazione desiderato a un testo, restituendo markup HTML.
	*
	* Gli stili supportati sono:
	* - `mark`: evidenziazione standard (<mark>)
	* - `underline`: sottolineatura con classe CSS (<span class="np-underline">)
	* - `both`: evidenziazione + sottolineatura (<mark class="np-underline">)
	*
	* Il testo viene automaticamente sanificato via `htmlspecialchars` per evitare problemi XSS o markup errato.
	*
	* @param string $text Testo da evidenziare
	* @param string $style Tipo di evidenziazione: 'mark' (default), 'underline', 'both'
	* @return string Testo HTML evidenziato secondo lo stile specificato
	*
	* @example fn_wrap_highlight('CMS', 'mark'); // <mark>CMS</mark>
	* @example fn_wrap_highlight('CMS', 'underline'); // <span class="np-underline">CMS</span>
	* @example fn_wrap_highlight('CMS', 'both'); // <mark class="np-underline">CMS</mark>
	*/

	function fn_wrap_highlight(string $text, string $style): string {
		$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
		return match ($style) {
			'underline' => '<span class="np-underline">' . $text . '</span>',
			'both' => '<mark class="np-underline">' . $text . '</mark>',
			default => '<mark>' . $text . '</mark>',
		};
	}
	/* ----------------- END ----------------- */

	/*
	* Calcola statistiche testuali su una stringa: parole, caratteri, frasi, paragrafi, spazi e densità.
	*
	* Le parole di 1 o 2 caratteri vengono escluse dal calcolo della densità,
	* così da ottenere un valore più rappresentativo e percepito (non distorto da articoli e congiunzioni).
	*
	* Il campo `density` restituisce solo le parole:
	* - con almeno 2 occorrenze
	* - che rappresentano ≥ 10% delle parole significative
	*
	* Utile per:
	* - editor visuali (statistiche live)
	* - anteprime e validazioni contenuto
	* - dashboard e analisi SEO interna
	*
	* @param string $text Il contenuto da analizzare
	* @return array Associativo con le seguenti chiavi:
	*   - 'characters' → caratteri totali (con spazi)
	*   - 'characters_no_space' → caratteri senza spazi
	*   - 'spaces' → numero di spazi nel testo
	*   - 'words' → numero totale di parole
	*   - 'paragraphs' → numero di paragrafi (testo separato da newline doppio)
	*   - 'sentences' → numero di frasi (punteggiatura terminale + spazio)
	*   - 'avg_words_per_sentence' → media parole per frase
	*   - 'avg_words_per_paragraph' → media parole per paragrafo
	*   - 'density' → array con parole significative e loro percentuale di presenza
	*
	* @example
	* $stats = fn_str_stats($contenuto);
	* echo $stats['words'] . ' parole, ' . $stats['paragraphs'] . ' paragrafi';
	*/

	function fn_str_stats(string $text, array $keywordsList = [], bool $ignoreNewlines = true): array {

		$text = strip_tags($text);

		$text = strip_tags($text);
		if ($ignoreNewlines) {
			$charTotal = mb_strlen(str_replace(["\n"], '', $text));
		} else {
			$charTotal = mb_strlen($text);
		}
		$charNoSpace = mb_strlen(str_replace([' ', "\t", "\n", "\r"], '', $text));
		$spaces = substr_count($text, ' ');

		$wordsArray = [];
		$totalValidWords = 0;
		$wordCount = preg_match_all('/\b\p{L}+\b/u', $text, $matches);

		if ($wordCount) {
			foreach ($matches[0] as $word) {
				$len = mb_strlen($word);
				if ($len <= 2) continue; // Esclude parole troppo brevi
				$w = mb_strtolower($word);
				$wordsArray[$w] = ($wordsArray[$w] ?? 0) + 1;
				$totalValidWords++;
			}
		}

		$paragraphs = preg_match_all('/(\r?\n){2,}/', $text, $p) + 1;
		$sentences = preg_match_all('/[.!?]+[\s\r\n]/u', $text . ' ', $s);

		$density = [];
		foreach ($wordsArray as $word => $count) {
			if ($count >= 2) {
				$percent = ($totalValidWords > 0) ? ($count / $totalValidWords * 100) : 0;
				$density[$word] = round($percent, 2);
			}
		}


		$perceived = [];
		$totalPerceivedWords = 0;

		// Prima passata: raccogli solo parole del set che compaiono almeno 2 volte
		foreach ($keywordsList as $keyword) {
			$word = mb_strtolower(trim($keyword));
			if (isset($wordsArray[$word]) && $wordsArray[$word] >= 2) {
				$perceived[$word] = $wordsArray[$word];
				$totalPerceivedWords += $wordsArray[$word];
			}
		}

		// Seconda passata: calcola la percentuale percepita
		if ($totalPerceivedWords > 0) {
			foreach ($perceived as $word => $count) {
				$perceived[$word] = round(($count / $totalPerceivedWords) * 100, 2);
			}
		} else {
			$perceived = []; // se nessuna keyword supera la soglia, restituiamo array vuoto
		}

		return [
			'characters' => $charTotal,
			'characters_no_space' => $charNoSpace,
			'spaces' => $spaces,
			'words' => $wordCount,
			'paragraphs' => $paragraphs,
			'sentences' => $sentences,
			'avg_words_per_sentence' => $sentences ? round($wordCount / $sentences, 2) : 0,
			'avg_words_per_paragraph' => $paragraphs ? round($wordCount / $paragraphs, 2) : 0,
			'density' => $density,
			'perceived' => $perceived
		];
	}
	/* ----------------- END ----------------- */

// =============================================================
// ICON SYSTEM – versione unificata: sprite + <use> per TUTTI gli stili
// - Colore e size controllati da classi (.text-*, .fs*)
// - ID <symbol> univoco per OGNI istanza (per pagina)
// - Back-compat: ng_icon() resta per uso inline legacy (non usata dal flow standard)
// =============================================================

	function ng_icon(string $name, string $style = 'regular', array $attrs = []): string {

		$baseDir = alias('ngicons:' . $style . '/', false);
		$file = $baseDir . $name . '.svg';
		if (!file_exists($file)) return '';
		$svg = file_get_contents($file);
		if (!$svg) return '';

		if ($style === 'duotone') {
			$svg = preg_replace(
				'#(<(?:path|rect|polygon)[^>]*?opacity=["\']0\.\d+["\'][^>]*?)fill=["\'][^"\']*["\']#i',
				'$1fill="var(--ng-duo-secondary, currentColor)"',
				$svg
			);
			$svg = preg_replace(
				'#(<(?:path|rect|polygon)(?![^>]*?opacity=["\']0\.\d+)[^>]*?)fill=["\'][^"\']*["\']#i',
				'$1fill="currentColor"',
				$svg
			);
		} elseif ($style === 'fill') {
			$svg = preg_replace('#(<(?:path|rect|polygon)[^>]*?)fill=["\'][^"\']*["\']#i', '$1fill="currentColor"', $svg);
		} elseif (in_array($style, ['regular', 'bold', 'thin', 'light'], true)) {
			$svg = preg_replace('#stroke=["\'][^"\']*["\']#i', 'stroke="currentColor"', $svg);
			$svg = preg_replace_callback('#<path([^>]*)>#i', function ($m) {
				return (stripos($m[1], 'stroke=') === false && stripos($m[1], 'fill=') === false)
					? '<path' . $m[1] . ' fill="currentColor">'
					: '<path' . $m[1] . '>';
			}, $svg);
		}

		if (!isset($attrs['fill']) && in_array($style, ['regular', 'thin', 'light', 'bold'], true)) {
			$attrs['fill'] = 'none';
		}

		if (!empty($attrs)) {
			$parts = explode('<svg', $svg, 2);
			if (count($parts) === 2) {
				$attrString = '';
				foreach ($attrs as $key => $value) $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
				$svg = '<svg' . $attrString . ' ' . $parts[1];
			}
		}

		return $svg;
	}

	function ng_replace_icons(string $html, array &$usedIcons = []): string {

		$iconMain = [];
		$iconToggle = [];

		$html = preg_replace_callback('/<ngi([^>]*)\s*\/?>/', function ($m) use (&$iconMain, &$iconToggle) {
			$attrs = [];
			preg_match_all('/([\w\-:]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/', $m[1], $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$key = $match[1];
				$value = $match[2] ?? $match[3] ?? $match[4] ?? '';
				$attrs[$key] = $value;
			}
			if (empty($attrs['name'])) return '';

			$name   = trim($attrs['name']);
			$style  = trim($attrs['data-style'] ?? 'regular');
			$toggle = trim($attrs['data-icon-alt'] ?? '');

			if (!isset($attrs['class'])) $attrs['class'] = 'ng-icon';
			else $attrs['class'] = 'ng-icon ' . $attrs['class'];

			if ($style === 'duotone') {
				if (!isset($attrs['style'])) $attrs['style'] = '--ng-duo-secondary: currentColor;';
				else $attrs['style'] .= ';--ng-duo-secondary: currentColor;';
			}

			$attrs['data-name'] = $name;

			// ID univoci random (sempre)
			$uid    = $name   . '-' . random_int(10000, 99999);
			$altUid = $toggle ? ($toggle . '-' . random_int(10000, 99999)) : '';

			// Registra SEMPRE per lo sprite (anche fill/duotone)
			$iconMain[] = $style . ':' . $name . '#' . $uid;
			if ($toggle !== '') {
				$iconToggle[] = $style . ':' . $toggle . '#' . $altUid;
				$attrs['data-icon-alt-id'] = $altUid;
				$attrs['data-icon-alt'] = $toggle;
			}

			$attrs['data-icon-id'] = $uid;
			unset($attrs['name'], $attrs['data-style']);

			// Output: inline per fill/duotone, <use> per gli altri
			if (in_array($style, ['duotone', 'fill'], true)) {
				return ng_icon($name, $style, $attrs);
			} else {
				$attrString = '';
				foreach ($attrs as $k => $v) $attrString .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
				return '<svg' . $attrString . '><use href="#' . $uid . '" /></svg>';
			}
		}, $html);

		$usedIcons = array_unique(array_merge($iconMain, $iconToggle));
		return $html;
	}

	function ng_icon_sprite(array $icons): string {

		if (empty($icons)) return '';

		$out = '<svg xmlns="http://www.w3.org/2000/svg" style="display: none">';

		foreach ($icons as $entry) {
			if (strpos($entry, '#') === false) continue;
			[$styleName, $uid] = explode('#', $entry, 2);
			if (strpos($styleName, ':') === false) continue;
			[$style, $name] = explode(':', $styleName, 2);

			$baseDir = alias('ngicons:' . $style . '/', false);
			$file = $baseDir . $name . '.svg';
			if (!file_exists($file)) continue;

			$svg = file_get_contents($file);
			if (!$svg) continue;

			if ($style === 'duotone') {
				$svg = preg_replace(
					'#(<(?:path|rect|polygon)[^>]*?opacity=["\']0\.\d+["\'][^>]*?)fill=["\'][^"\']*["\']#i',
					'$1fill="var(--ng-duo-secondary, currentColor)"',
					$svg
				);
				$svg = preg_replace(
					'#(<(?:path|rect|polygon)(?![^>]*?opacity=["\']0\.\d+)[^>]*?)fill=["\'][^"\']*["\']#i',
					'$1fill="currentColor"',
					$svg
				);
			} elseif ($style === 'fill') {
				$svg = preg_replace('#(<(?:path|rect|polygon)[^>]*?)fill=["\'][^"\']*["\']#i', '$1fill="currentColor"', $svg);
			} elseif (in_array($style, ['regular', 'bold', 'thin', 'light'], true)) {
				$svg = preg_replace('#stroke=["\'][^"\']*["\']#i', 'stroke="currentColor"', $svg);
			}

			$svg = preg_replace_callback(
				'#<(path|circle|ellipse|rect|polygon)([^>]*)/?\>#i',
				function ($m) {
					$full = $m[0];
					$attrs = $m[2];
					$hasFill = stripos($attrs, 'fill=') !== false;
					$hasStroke = stripos($attrs, 'stroke=') !== false;
					if ($hasFill || $hasStroke) return $full;
					if (substr($full, -2) === '/>') return substr($full, 0, -2) . ' fill="currentColor"/>';
					return substr($full, 0, -1) . ' fill="currentColor">';
				},
				$svg
			);

			$svg = preg_replace('#^<svg[^>]*>#', '', $svg);
			$svg = preg_replace('#</svg>$#', '', $svg);

			$out .= '<symbol id="' . $uid . '" viewBox="0 0 256 256">' . $svg . '</symbol>';
		}

		$out .= '</svg>';
		return $out;
	}

// ==================================================
// === [OTHERS] Funzioni ... ===
// === ...
// ==================================================

	function show_code($code, $lang = 'html') {
		echo '<pre><code class="close language-' . $lang . '">';
		echo htmlspecialchars(trim($code));
		echo '</code></pre>';
	}

	/*
	* Restituisce una label testuale che indica quanto manca o quanto è manca ad una scadenza.
	* @param string|null $date Data in formato 'Y-m-d' o compatibile. Null per assenza di scadenza.
	* @return string Label HTML con indicazione della scadenza (oggi, tra X giorni, scaduto).
	*/
	function task_deadline_label(?string $date): string {

		if (!$date) return '—';

		$today = new DateTimeImmutable(date('Y-m-d'));
		$due = new DateTimeImmutable(substr($date, 0, 10));
		$diff = (int)$today->diff($due)->format('%r%a');

		if ($diff === 0) {
			return 'Due <b class="done">today</b>';
		} elseif ($diff > 0) {
			return 'Due in <span class="done">' . $diff . '</<span> day' . ($diff > 1 ? 's' : '');
		} else {
			return 'Expired by <b class="orange">' . abs($diff) . '</b> day' . (abs($diff) > 1 ? 's' : '');
		}
	}
	/* ----------------- END ----------------- */

// === FINE ===