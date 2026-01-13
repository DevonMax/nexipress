<?php
/* --------------------------------------------------------------------------------------
* Bootstrap NexiPress – Initialization System
* -------------------------------------------------------------------------------------- */

/* ======================================================================
* 1. Runtime Environment & Core Constants
* ----------------------------------------------------------------------
* X_* = Costanti runtime usate dal core
* ====================================================================== */

if (!defined('X_ENV'))   define('X_ENV',   Config::get('env')   ?? 'production');
if (!defined('X_DEBUG')) define('X_DEBUG', Config::get('debug') ?? 'mute');
if (!defined('X_ROOT'))  define('X_ROOT',  rtrim(dirname(__FILE__, 2), '/') . '/');

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (!defined('X_PAGE')) define('X_PAGE', trim($uriPath, '/'));


/* ======================================================================
* 2. Alias Resolution & Path Mapping
* ----------------------------------------------------------------------
* Costruzione mappa alias runtime
* ====================================================================== */

$theme = Config::get('thm_active') ?? 'default';
$map   = Config::get('alias_map');
$alias = [];

foreach ($map as $key => $path) {

	// Placeholder dinamici
	if (str_contains($path, '{theme}')) {
		$path = str_replace('{theme}', $theme, $path);
	}

	// Path assoluto normalizzato
	$alias[$key] = rtrim(X_ROOT . $path, '/') . '/';
}

/* ======================================================================
* 3. Core Dependencies & System Guards
* ----------------------------------------------------------------------
* Autoload, funzioni globali, database, sicurezza base
* ====================================================================== */

require_once NP_ROOT . '/vendor/autoload.php';
require_once NP_CORE . '/function.php';
require_once NP_CORE . '/db.php';

/* -- IP Whitelist ----------------------------------------------------- */

$allowed = Config::get('allowed_ips');
if (!empty($allowed) && !ip_in_allowed($allowed, $_SERVER['REMOTE_ADDR'])) {
	http_response_code(403);
	nexi_render_error('Forbidden', 'Accesso non autorizzato.', 403, __FILE__, __LINE__);
}

/* ======================================================================
* 4. Context Bootstrap
* ----------------------------------------------------------------------
* Inizializzazione contesto globale
* ====================================================================== */

ctx_bootstrap_config();
ctx::set('alias.map', $alias);


/* ======================================================================
* 5. Application Language (Frontend)
* ----------------------------------------------------------------------
* Lingua sito / applicazione
* ====================================================================== */

$locale = nexi_locale_boot($_SERVER['REQUEST_URI'] ?? '/');

if (!$locale) {
	$locale = Config::get('lang_default')
		?? Config::get('lang_fallback')
		?? 'en';
}

ctx::set('lang.current', $locale);

/* ======================================================================
* 6. System Language (Framework)
* ----------------------------------------------------------------------
* Lingua interna del framework
* ====================================================================== */

$langSys = require NP_ROOT . '/system/locale/config-locale.php';

ctx::set('lang.sys.current',  $langSys['lang_sys_current']);
ctx::set('lang.sys.list',     $langSys['lang_sys_list']);
ctx::set('lang.sys.fallback', $langSys['lang_sys_fallback']);

unset($langSys);

/* ======================================================================
* 7. Theme Configuration
* ----------------------------------------------------------------------
* Tema attivo e fallback
* ====================================================================== */

ctx::set('theme.active',   Config::get('thm_active'));
ctx::set('theme.fallback', Config::get('thm_fallback'));

/* ======================================================================
* 8. Secure Keys Initialization
* ----------------------------------------------------------------------
* Chiavi runtime / sicurezza
* ====================================================================== */

$__keys = fn_load_secure_keys();

define('KEY_STT', $__keys['KEY_STT']);
define('KEY_API', $__keys['KEY_API']);

/* ======================================================================
* 9. Error & Exception Handling
* ----------------------------------------------------------------------
* Hook globali di errore
* ====================================================================== */

set_error_handler('nexi_error_handler');
set_exception_handler('nexi_exception_handler');
register_shutdown_function('nexi_fatal_handler');