<?php
/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
| Gestione centralizzata della configurazione NexiPress.
|
| - Carica un array di configurazione globale (config.php)
| - Accesso tramite dot notation (annidata)
| - Pensata per valori STATICI (boot-time)
|
| NON è runtime state
| NON è request-aware
|--------------------------------------------------------------------------
*/
class Config
{
	/**
	* Storage interno configurazione.
	* Array multidimensionale.
	*/
	protected static array $data = [];

	/**
	* Carica l'intero array di configurazione.
	* Sovrascrive tutto.
	*/
	public static function load(array $config): void {
		self::$data = $config;
	}

	/**
	* Imposta un valore usando dot notation.
	* Crea i livelli intermedi se mancanti.
	*/
	public static function set(string $path, $value): void {
		$keys = array_map(fn($k) => rtrim($k, ':'), explode('.', $path));
		$dset =& self::$data;

		foreach ($keys as $key) {

			// Normalizza chiavi con/senza :
			$normalized = [];
			foreach ($dset as $k => $v) {
				$normalized[rtrim($k, ':')] = $v;
			}
			$dset = $normalized;

			if (!isset($dset[$key]) || !is_array($dset[$key])) {
				$dset[$key] = [];
			}
			$dset =& $dset[$key];
		}

		$dset = $value;
	}

	/**
	* Verifica esistenza chiave (dot notation).
	*/
	public static function has(string $path): bool {
		$keys = array_map(fn($k) => rtrim($k, ':'), explode('.', $path));
		$dget = self::$data;

		foreach ($keys as $key) {
			$normalized = [];
			foreach ($dget as $k => $v) {
				$normalized[rtrim($k, ':')] = $v;
			}
			$dget = $normalized;

			if (!is_array($dget) || !array_key_exists($key, $dget)) {
				return false;
			}
			$dget = $dget[$key];
		}
		return true;
	}

	/**
	* Recupera un valore con fallback.
	*/
	public static function get(string $path, $default = null) {
		$keys = explode('.', $path);
		$dget = self::$data;

		foreach ($keys as $key) {
			if (!is_array($dget) || !array_key_exists($key, $dget)) {
				return $default;
			}
			$dget = $dget[$key];
		}
		return $dget;
	}

	/**
	* Ritorna l'intera configurazione.
	* Opzionale output formattato.
	*/
	public static function all(bool $isFormat = false): array {
		$data = self::$data;
		if ($isFormat) {
			echo formatArray($data, true);
		}
		return $data;
	}
}
/* ----------------- END ----------------- */

/*
|--------------------------------------------------------------------------
| ctx
|--------------------------------------------------------------------------
| Contesto globale della richiesta corrente.
|
| - Dati runtime
| - Parametri di routing
| - Stato dinamico (lingua, alias, modalità)
|
| NON persiste
| NON è configurazione
|--------------------------------------------------------------------------
*/
class ctx
{
	/**
	* Storage interno del contesto runtime.
	* Contiene dati validi SOLO per la richiesta corrente.
	*/
	private static array $data = [];

	/**
	* Imposta un valore nel contesto.
	* Usata per salvare dati runtime condivisi
	* (es. parametri di routing, lingua attiva, modalità, ecc.).
	*
	* @param string $key   Chiave identificativa (anche con dot notation)
	* @param mixed  $value Valore da associare
	* @return void
	*/
	public static function set($key, $value): void {
		self::$data[$key] = $value;
	}

	/**
	* Recupera un valore dal contesto.
	* Se la chiave non esiste restituisce il default.
	* NON genera errori.
	*
	* @param string $key        Chiave da leggere
	* @param mixed  $default   Valore di fallback (default: 'false')
	* @return mixed
	*/
	public static function get($key, $default = 'false') {
		return self::$data[$key] ?? $default;
	}

	/**
	* Verifica se una chiave esiste nel contesto.
	*
	* @param string $key Chiave da verificare
	* @return bool
	*/
	public static function has($key): bool {
		return array_key_exists($key, self::$data);
	}

	/**
	* Rimuove una singola chiave dal contesto.
	* Usata per pulizia mirata di stato runtime.
	*
	* @param string $key Chiave da eliminare
	* @return void
	*/
	public static function forget($key): void {
		unset(self::$data[$key]);
	}

	/**
	* Svuota completamente il contesto runtime.
	* Usata tipicamente a fine richiesta o in reset forzati.
	*
	* @return void
	*/
	public static function flush(): void {
		self::$data = [];
	}

	/**
	* Restituisce l'intero contenuto del contesto.
	* Se $isFormat è true, stampa anche una versione formattata
	* a scopo di debug, senza influire sul return.
	*
	* @param bool $isFormat Output di debug formattato
	* @return array
	*/
	public static function all(bool $isFormat = false): array {
		$data = self::$data;
		if ($isFormat) {
			echo formatArray($data, true);
		}
		return $data;
	}
}

/**
* Inizializza configurazioni essenziali direttamente nel contesto (ctx).
* Tutte le chiavi sono piatte per accesso diretto: es. ctx::get('env') o ctx::get('db.analytics.pass')
*
* @return void
*/
function ctx_bootstrap_config(): void
{
	ctx::set('env', Config::get('env'));
	ctx::set('mode', Config::get('app_mode'));
	ctx::set('name', Config::get('app_name'));
	ctx::set('ver', Config::get('version'));
	ctx::set('url', Config::get('base_url'));
	ctx::set('zone', Config::get('app_timezone'));
	ctx::set('debug', Config::get('debug'));
	ctx::set('log', Config::get('log_dir'));

	// Flatten database config
	if ($db = Config::get('databases')) {
		foreach ($db as $dbKey => $params) {
			foreach ($params as $k => $v) {
				if($k === 'user'){
					ctx::set("db.$dbKey.$k", "your-user-choice");
				} else if($k === 'pass'){
					ctx::set("db.$dbKey.$k", "your-password-choice");
				} else {
					ctx::set("db.$dbKey.$k", $v);
				}
			}
		}
	}
}

/**
* Visualizza il contenuto del contesto ctx in modo leggibile con hightlight.
*
* @param bool $highlight Se true, usa highlight_string per output formattato (default: true)
* @param bool $isPre Preformatta o no array ctx (default: true)
* @return void
*/
function ctx_dump(bool $highlight = true, bool $isPre = false): void
{
	$preStart = $isPre ? "<pre>" : "";
	$preEnd   = $isPre ? "</pre>" : "";
	$data = '<?php ' . var_export(ctx::all(), true) . ' ?>';

	if ($highlight) {
		echo $preStart;
		highlight_string($data);
		echo $preEnd;
	} else {
		echo $preStart . $data . $preEnd;
	}
}
/* ----------------- END ----------------- */

/*
|--------------------------------------------------------------------------
| nexi_i18n
|--------------------------------------------------------------------------
| Sistema di internazionalizzazione NexiPress.
|
| - Carica file lingua locali
| - Supporta sezioni multiple
| - Normalizza tutto in dot notation
| - Implementa pluralizzazione stile Laravel
|
| NON dipende dal router
| NON dipende dal template engine
|--------------------------------------------------------------------------
*/
class nexi_i18n
{
	/**
	* Storage statico delle traduzioni caricate.
	* Chiavi sempre in dot notation.
	*/
	private static array $data = [];

	/**
	* Carica una o più sezioni da un file lingua.
	* Esempio:
	* nexi_i18n::loadSections('it', ['ecommerce','home']);
	*/
	public static function loadSections(string $locale, array $sections): void
	{
		$file = alias('lang:' . $locale . '.php', false);

		if (!file_exists($file)) {
			nexi_render_error(
				'nexi_lang.language_file_invalid_title',
				'nexi_lang.language_file_invalid_message',
				500
			);
		}

		$raw = require $file;

		if (!is_array($raw)) {
			nexi_render_error(
				'nexi_lang.language_unsupported_title',
				'nexi_lang.language_unsupported_message',
				500
			);
		}

		// Sezione condivisa sempre caricata
		$shared = $raw['_shared'] ?? [];
		$flatShared = self::flatten('_shared', $shared);
		self::$data = array_merge(self::$data, $flatShared);

		// Sezioni richieste esplicitamente
		foreach ($sections as $section) {
			if (!isset($raw[$section])) continue;
			$flat = self::flatten($section, $raw[$section]);
			self::$data = array_merge(self::$data, $flat);
		}
	}

	/**
	* Trasforma array annidato in dot notation.
	*/
	private static function flatten(string $prefix, array $arr): array
	{
		$out = [];

		foreach ($arr as $k => $v) {
			$key = $prefix . '.' . $k;

			if (is_array($v)) {
				$out = array_merge($out, self::flatten($key, $v));
			} else {
				$out[$key] = $v;
			}
		}

		return $out;
	}

	/**
	* Imposta o sovrascrive una singola voce.
	*/
	public static function set(string $key, mixed $value): void
	{
		self::$data[$key] = $value;
	}

	/**
	* Recupera una traduzione.
	*/
	public static function get(string $key, mixed $default = null): mixed
	{
		return self::$data[$key] ?? $default;
	}

	/**
	* Gestisce pluralizzazione stile Laravel.
	*/
	public static function choice(string $key, int $count, array $replacements = []): string
	{
		$raw = self::get($key);

		if (!is_string($raw) || !str_contains($raw, '|')) {
			return is_string($raw) ? $raw : '';
		}

		$segments = explode('|', $raw);
		$selected = null;

		foreach ($segments as $segment) {
			if (preg_match('/^\s*([\{\[])([^}\]]+)[\}\]]\s*(.+)$/', $segment, $m)) {
				$type  = $m[1];
				$range = $m[2];
				$text  = $m[3];

				if ($type === '{') {
					if (ctype_digit($range) && (int)$range === $count) {
						$selected = trim($text);
						break;
					}
				} else {
					[$start, $end] = array_map('trim', explode(',', $range));
					$startOk = ($start === '*' || $count >= (int)$start);
					$endOk   = ($end   === '*' || $count <= (int)$end);

					if ($startOk && $endOk) {
						$selected = trim($text);
						break;
					}
				}
			}
		}

		if ($selected === null) {
			$selected = trim(end($segments));
		}

		$replacements['count'] = $count;

		foreach ($replacements as $k => $v) {
			$selected = str_replace(':' . $k, $v, $selected);
		}

		return $selected;
	}

	/**
	* Verifica se una chiave esiste.
	*/
	public static function has(string $key): bool
	{
		return array_key_exists($key, self::$data);
	}

	/**
	* Rimuove una chiave.
	*/
	public static function forget(string $key): bool
	{
		if (array_key_exists($key, self::$data)) {
			unset(self::$data[$key]);
			return true;
		}
		return false;
	}

	/**
	* Svuota tutte le traduzioni caricate.
	*/
	public static function flush(): void
	{
		self::$data = [];
	}

	/**
	* Ritorna tutte le traduzioni.
	*/
	public static function all(): array
	{
		return self::$data;
	}
}

// ----------------------------------------------------------------------
// HELPER GLOBALI i18n
// ----------------------------------------------------------------------

// Alias breve per nexi_i18n
class_alias('nexi_i18n', 'lok');

// Accesso rapido a get()
function _k(string $key, mixed $default = null): mixed {
	return nexi_i18n::get($key, $default);
}

// Accesso rapido a choice()
function _kc(string $key, int $count, array $vars = []): string {
	return nexi_i18n::choice($key, $count, $vars);
}
/* ----------------- END ----------------- */

/*
|--------------------------------------------------------------------------
| apiCall
|--------------------------------------------------------------------------
| Client HTTP/API centralizzato.
|
| - Configurazione via Config::get('api.*')
| - Supporto GET / POST / PUT / DELETE
| - Logging automatico
| - Parsing headers + JSON
|--------------------------------------------------------------------------
*/
class apiCall
{
	public static function get(string|array $keyOrOptions, array $params = []): array {
		return self::request('GET', $keyOrOptions, $params);
	}

	public static function post(string|array $keyOrOptions, array $params = []): array {
		return self::request('POST', $keyOrOptions, $params);
	}

	public static function put(string|array $keyOrOptions, array $params = []): array {
		return self::request('PUT', $keyOrOptions, $params);
	}

	public static function delete(string|array $keyOrOptions, array $params = []): array {
		return self::request('DELETE', $keyOrOptions, $params);
	}

	public static function success(array $res): bool {
		return isset($res['status']) &&
			$res['status'] === 200 &&
			empty($res['error']);
	}

	protected static function request(string $method, string|array $input, array $params = []): array {
		$options = is_string($input) ? Config::get("api.$input") : $input;
		if (!is_array($options) || empty($options['url'])) {
			return [
				'status' => 500,
				'data'   => [],
				'error'  => 'API endpoint not configured'
			];
		}

		$url      = $options['url'];
		$apiKey   = $options['api_key'] ?? null;
		$authName = $options['auth_key'] ?? 'Authorization';
		$authType = $options['auth_type'] ?? 'bearer';
		$headers  = $options['headers'] ?? [];
		$timeout  = $options['timeout'] ?? 5;
		$deleteWithBody = $options['delete_body'] ?? false;

		if ($method === 'GET' && !empty($params)) {
			$query = http_build_query($params);
			$url .= (str_contains($url, '?') ? '&' : '?') . $query;
		}

		if ($apiKey && $authName) {
			$prefix = strtolower($authType) === 'bearer' ? 'Bearer ' : '';
			$headers[] = "$authName: $prefix$apiKey";
		}
		$headers[] = 'Accept: application/json';

		$sendBody = in_array($method, ['POST', 'PUT']) || ($method === 'DELETE' && $deleteWithBody);
		if ($sendBody && !empty($params)) {
			$headers[] = 'Content-Type: application/json';
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HEADER, true); // per estrarre headers

		if ($sendBody && !empty($params)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$start = microtime(true);
		$response = curl_exec($ch);
		$elapsed = round((microtime(true) - $start) * 1000); // in ms
		$code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		curl_close($ch);

		$rawHeaders = substr($response, 0, $headerSize);
		$body       = substr($response, $headerSize);
		$parsedHeaders = self::parseHeaders($rawHeaders);

		$json  = [];
		$error = null;
		$err_mex = 'The request did not produce any results.';

		if ($code >= 200 && $code < 300 && $body) {
			$decoded = json_decode($body, true);
			$isValid = is_array($decoded);
			$isEmpty = $isValid && empty($decoded);

			$json  = $isValid ? $decoded : [];
			if (!$isValid) {
				$error = 'The API returned non-JSON content.';
			} elseif ($isEmpty) {
				$error = $err_mex;
			}
		} else {
			$error = 'Unable to reach host or connection timed out. The API response was invalid or empty.';
		}

		if ($code >= 400 || ($error !== null && Config::get('debug') === 'display')) {
			self::log($method, $url, $code, $body, $params, $apiKey, $elapsed, $error, $parsedHeaders);
		}

		if ($code >= 400) {
			$error = match (true) {
				$code === 401 => 'Authentication failed',
				$code === 403 => 'Access denied',
				$code === 404 => 'Resource not found',
				$code >= 500  => 'API internal error',
				default       => 'Generic error'
			};
		}

		return [
			'status'  => $code,
			'data'    => $json,
			'error'   => $error,
			'headers' => $parsedHeaders
		];
	}

	protected static function parseHeaders(string $raw): array {
		$headers = [];
		foreach (explode("\r\n", $raw) as $line) {
			if (strpos($line, ':') !== false) {
				[$key, $value] = explode(':', $line, 2);
				$headers[strtolower(trim($key))] = trim($value);
			}
		}
		return $headers;
	}

	protected static function log(string $method, string $url, int $code, string $response, array $params, ?string $apiKey, int $elapsed, string $err_mex, array $headers): void {

		$log_dir = rtrim(Config::get('log'), '/'); // usa il path come da config
		$week       = date('o-W');
		$log_url = rtrim($log_dir, '/') . "/api-log-week-$week.log";
		$log_date   = date('Y-m-d H:i:s');
		$log_header = "=== [$log_date] ===\n";
		$requestId  = $headers['x-request-id'] ?? '(none)';

		$log_body = "[$method] $url\n";
		$log_body .= "TIME: {$elapsed}ms\n";
		$log_body .= "CODE: $code\n";
		$log_body .= "X-REQUEST-ID: $requestId\n";
		$log_body .= "PARAMS: " . json_encode($params) . "\n";
		$log_body .= "ERR.MEX: $err_mex\n";
		// $log_body .= "RESPONSE: $response\n\n";

		if (!file_exists($log_url)) {
			file_put_contents($log_url, "\n$log_header", FILE_APPEND);
		} else {
			$log_contents = @file_get_contents($log_url);
			if (strpos($log_contents, $log_header) === false) {
				file_put_contents($log_url, "\n$log_header", FILE_APPEND);
			}
		}
		file_put_contents($log_url, $log_body, FILE_APPEND);
	}
}
/* ----------------- END ----------------- */

/*
|--------------------------------------------------------------------------
| formatArray
|--------------------------------------------------------------------------
| Helper di debug.
| Restituisce SEMPRE una stringa.
|--------------------------------------------------------------------------
*/
function formatArray(array $data, bool $isFormat = false): string
{
	if ($isFormat) {
		$code = '<?php ' . var_export($data, true) . ' ?>';
		return highlight_string($code, true); // <-- usa $return=true
	}
	return '<pre>' . htmlspecialchars(print_r($data, true), ENT_QUOTES, 'UTF-8') . '</pre>';
}
/* ----------------- END ----------------- */




