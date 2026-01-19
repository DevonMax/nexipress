<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| NexiPress – Entry Point
|--------------------------------------------------------------------------
| Questo file è l’unico punto di ingresso dell’intera applicazione.
| Ha SOLO tre responsabilità:
|
| 1. Definire i percorsi base del framework
| 2. Verificare i requisiti minimi dell’ambiente (PHP + estensioni)
| 3. Avviare bootstrap e sistema di routing
|
| Qualsiasi errore critico qui BLOCCA l’esecuzione.
|--------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Static Paths (NP_*)
// Absolute paths needed to start the framework
// ----------------------------------------------------------------------

define('NP_ROOT', __DIR__);
define('NP_APP',  NP_ROOT . '/application');
define('NP_CORE', NP_ROOT . '/core');

// ----------------------------------------------------------------------
// Core minimal load
// class.php      -> config / contest ctx() / language
// error-handler  -> rendering fatal error
// ----------------------------------------------------------------------

require_once NP_CORE . '/class.php';
require_once NP_CORE . '/error-handler.php';

// ----------------------------------------------------------------------
// Environment Guard – PHP Version
// NexiPress request PHP >= 8.0
// ----------------------------------------------------------------------

if (version_compare(PHP_VERSION, '8.0.0', '<')) {

	nexi_render_error(
		'Version PHP Error',
		'PHP ' . phpversion() . ' detected. NexiPress requires PHP 8.0 or higher.',
		500,
		__FILE__,
		__LINE__
	);
	exit;
}

// ----------------------------------------------------------------------
// Environment Guard – Required Extensions
// Minimum extensions essential to the core
// ----------------------------------------------------------------------

$requiredExt = ['pdo', 'mbstring', 'json', 'intl'];
$missing = array_values(
	array_filter($requiredExt, static fn ($ext) => !extension_loaded($ext))
);

if ($missing) {
	nexi_render_error(
		'Extension PHP Error',
		'Fatal error. Missing extensions: ' . implode(', ', $missing),
		500,
		__FILE__,
		__LINE__
	);
	exit;
}

// ----------------------------------------------------------------------
// Configuration & Runtime Bootstrap
// ----------------------------------------------------------------------

Config::load(require NP_ROOT . '/config.php', 'system');
Config::load(
    require NP_ROOT . '/application/app.config.php',
    'app'
);
Config::setAppConfigFile(
    NP_ROOT . '/application/app.config.php'
);

// bootstrap.php -> setup contest runtime (session, locale, alias, hook, ecc.)
// router.php    -> final dispatch of the HTTP request
require_once NP_CORE . '/bootstrap.php';
require_once NP_CORE . '/router.php';