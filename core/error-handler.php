<?php
// ============================================================================
// NEXIPRESS – ERROR HANDLER
// ----------------------------------------------------------------------------
// Gestore centralizzato degli errori NexiPress.
//
// Responsabilità:
// - intercettare warning / notice PHP
// - intercettare eccezioni non gestite
// - intercettare errori fatali a runtime
// - decidere come renderizzare l’errore (debug / mute)
//
// Il comportamento è governato da config.php → chiave `debug`:
//
// - debug = 'display' → schermata dettagliata (debug.php)
// - debug = 'mute'    → pagina HTTP custom o fallback
//
// Tutti i flussi passano da:
// - nexi_render_error()
// - nexi_http_response()
// ============================================================================

/*
|--------------------------------------------------------------------------
| PHP Error Handler (warning, notice, ecc.)
|--------------------------------------------------------------------------
| Intercetta errori PHP non fatali e li inoltra
| al renderer centrale NexiPress.
|
| Restituisce sempre true per evitare l’output
| nativo di PHP.
|--------------------------------------------------------------------------
*/
function nexi_error_handler($errno, $errstr, $errfile, $errline): bool {
	nexi_render_error(
		'Server Error - Critical',
		$errstr,
		500,
		$errfile,
		$errline,
		print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true)
	);
	return true;
}

/*
|--------------------------------------------------------------------------
| Exception Handler globale
|--------------------------------------------------------------------------
| Gestisce eccezioni non intercettate (Throwable).
|--------------------------------------------------------------------------
*/
function nexi_exception_handler(Throwable $exception): void {
	nexi_render_error(
		'Unhandled exception (Eccezione non gestita)[nexi]',
		$exception->getMessage(),
		500,
		$exception->getFile(),
		$exception->getLine(),
		$exception->getTraceAsString()
	);
}

/*
|--------------------------------------------------------------------------
| Fatal Error Handler (shutdown)
|--------------------------------------------------------------------------
| Intercetta errori fatali a fine esecuzione.
|--------------------------------------------------------------------------
*/
function nexi_fatal_handler(): void {
	$error = error_get_last();
	if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
		nexi_render_error(
			'Fatal Error [nexi]',
			$error['message'],
			500,
			$error['file'],
			$error['line']
		);
	}
}

/*
|--------------------------------------------------------------------------
| HTTP Response Renderer
|--------------------------------------------------------------------------
| Mostra una pagina HTTP specifica (404.php, 500.php)
| oppure un fallback minimale.
|--------------------------------------------------------------------------
*/
function nexi_http_response($code) {

	http_response_code($code);

	$custom   = __DIR__ . '/../system/http_code/' . $code . '.php';
	$fallback = __DIR__ . '/../system/http_code/fallback.php';

	if (file_exists($custom)) {
		require $custom;
	} elseif (file_exists($fallback)) {
		ctx::set('response.http_code', $code);
		require $fallback;
	} else {
		echo "<h1>HTTP $code</h1>";
	}
	exit;
}
/* ----------------- END ----------------- */

/*
|--------------------------------------------------------------------------
| Error Renderer principale
|--------------------------------------------------------------------------
| Decide COME mostrare l’errore in base alla modalità debug.
|
| - debug=display → debug.php
| - debug=mute    → http_code/{code}.php o fallback
|
| Tutti i dati dell’errore vengono salvati in ctx
| per essere riutilizzati dai template.
|--------------------------------------------------------------------------
*/
function nexi_render_error(

	string $title,
	string $message,
	int $code = 500,
	?string $file = null,
	?int $line = null,
	?string $trace = null,
	?string $route_request = null

): void {

	http_response_code($code);

	$config = require NP_ROOT . '/config.php';
	$mode   = $config['debug'] ?? 'mute';
	$path   = __DIR__ . '/../system/http_code/';

	// Se manca il file → usa URL corrente
	if ($file === null) {
		$file = ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
	}

	// Fallback valori debug
	if ($line === null) {
		$line = 0;
	}
	if ($trace === null) {
		$trace = (new Exception())->getTrace();
	}

	// Persistenza dati debug nel contesto
	ctx::set('debug.http_code', $code);
	ctx::set('debug.title',     $title);
	ctx::set('debug.message',   $message);
	ctx::set('debug.file',      $file);
	ctx::set('debug.line',      $line);
	ctx::set('debug.trace',     $trace);
	ctx::set('debug.route',     $route_request);

	// Etichetta HTTP (404 → Not Found, ecc.)
	nexi_http_code_label($code);

	// Debug visivo
	if ($mode === 'display') {
		$debugFile = $path . 'debug.php';
		if (file_exists($debugFile)) {
			include $debugFile;
			exit;
		}
	}

	// Modalità mute / fallback
	$custom   = $path . "$code.php";
	$fallback = $path . 'fallback.php';

	if (file_exists($custom)) {
		include $custom;
	} elseif (file_exists($fallback)) {
		include $fallback;
	} else {
		echo "<h1>Errore $code</h1><p>" .
			htmlspecialchars($message, ENT_QUOTES, 'UTF-8') .
			"</p>";
	}

	exit;
}

/*
|--------------------------------------------------------------------------
| HTTP Code Label
|--------------------------------------------------------------------------
| Restituisce la descrizione testuale di un codice HTTP
| e la salva nel contesto debug.
|--------------------------------------------------------------------------
*/
function nexi_http_code_label(int $code): string
{
	static $labels = [
		// 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',

		// 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Payload Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',

		// 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	];

	$label = $labels[$code] ?? "$code";
	ctx::set('debug.label_system', $label);
	return $label;
}

/*
|--------------------------------------------------------------------------
| Error Safe Renderer
|--------------------------------------------------------------------------
| Versione “safe” dell’error renderer:
| - in debug → render completo
| - in mute  → solo HTTP response
|--------------------------------------------------------------------------
*/
function nexi_render_error_safe(string $title, string $message, int $code = 500): void {
	if (Config::get('debug') !== 'mute') {
		nexi_render_error($title, $message, $code);
	} else {
		nexi_http_response($code);
	}
}

/*
|--------------------------------------------------------------------------
| Extra Databases (safe)
|--------------------------------------------------------------------------
| Estrae DB secondari senza esporre credenziali.
|--------------------------------------------------------------------------
*/
function nexi_safe_extra_databases(array $config): ?array
{
	if (!isset($config['databases']) || !is_array($config['databases'])) {
		return null;
	}

	$extra = [];

	foreach ($config['databases'] as $name => $db) {
		if ($name === 'default') continue;
		if (!is_array($db)) continue;

		$prefix = strtoupper($name) . '_';
		$extra[$prefix . 'TYPE'] = $db['type'] ?? '-';
		$extra[$prefix . 'HOST'] = $db['host'] ?? '-';
		$extra[$prefix . 'DB']   = $db['name'] ?? '-';
		$extra[$prefix . 'LOG']  = $db['log'] ?? '-';
	}

	return empty($extra) ? null : $extra;
}

/*
|--------------------------------------------------------------------------
| Routing Debug Log
|--------------------------------------------------------------------------
| Log diagnostico del routing (solo in debug display).
|--------------------------------------------------------------------------
*/
function nexi_debug_log(array $data): void
{
	if (Config::get('routing_log') !== true) {
		return;
	}

	$logDir = rtrim(ctx::get('log') ?? '', '/');
	if ($logDir === '') return;

	$week = date('o-W');
	$file = $logDir . "/nexi-routing-week-$week.log";
	$time = date('Y-m-d H:i:s');

	is_dir(dirname($file)) || mkdir(dirname($file), 0775, true);

	$isNewFile = !file_exists($file) || filesize($file) === 0;
	$fp = fopen($file, 'a');

	// HEADER (una sola volta)
	if ($isNewFile) {
		fputcsv($fp, [
			'TIMESTAMP',
			'RESULT',
			'APP_MODE',
			'METHOD',
			'URI',
			'PAGE',
			'ROUTE_PATTERN',
			'ROUTE_REGEX',
			'CONTROLLER_FILE',
			'FILE_EXISTS',
			'PARAMS_JSON',
			'ERROR'
		]);
	}

	// ROW
	fputcsv($fp, [
		$time,
		$data['result']   ?? 'UNKNOWN',
		Config::get('app_mode') ?? '-',
		$_SERVER['REQUEST_METHOD'] ?? 'CLI',
		$_SERVER['REQUEST_URI'] ?? '-',
		$data['page']     ?? '-',
		$data['pattern']  ?? '',
		$data['regex']    ?? '',
		$data['file']     ?? '',
		isset($data['file']) ? (is_file($data['file']) ? 'YES' : 'NO') : '',
		isset($data['params']) ? json_encode($data['params'], JSON_UNESCAPED_UNICODE) : '',
		$data['error']    ?? ''
	], ',', '"', '\\');

	fclose($fp);
}

/*
|--------------------------------------------------------------------------
| Database Debug Log
|--------------------------------------------------------------------------
| Log diagnostico delle query DB (solo in debug display).
|--------------------------------------------------------------------------
*/
function nexi_db_log(string $dbKey, array $data): void
{
	if (Config::get('debug') !== 'display') return;

	$week     = date('o-W');
	$log_date = date('Y-m-d H:i:s');

	$log_dir  = rtrim(Config::get('log_dir'), '/') . '/';
	$log_file = $log_dir . "nexi-" . Config::get("databases.$dbKey.log") . "-week-$week.log";

	is_dir(dirname($log_file)) || mkdir(dirname($log_file), 0775, true);

	$isNewFile = !file_exists($log_file) || filesize($log_file) === 0;
	$fp = fopen($log_file, 'a');

	// HEADER (una sola volta)
	if ($isNewFile) {
		fputcsv($fp, [
			'TIMESTAMP',
			'DB_KEY',
			'QUERY',
			'PARAMS_JSON',
			'EXEC_TIME_MS'
		]);
	}

	// ROW
	fputcsv($fp, [
		$log_date,
		$dbKey,
		$data['query'] ?? '',
		json_encode($data['params'] ?? [], JSON_UNESCAPED_UNICODE),
		$data['time'] ?? null
	], ',', '"', '\\');


	fclose($fp);
}