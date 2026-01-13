<?php
/*
|--------------------------------------------------------------------------
| NexiPress – Router Entry
|--------------------------------------------------------------------------
| Questo file coordina il routing dichiarativo.
| Non interpreta URL manualmente e non contiene logica applicativa.
|
| Responsabilità:
| 1. Caricare i moduli di routing
| 2. Compilare / caricare la cache delle rotte
| 3. Avviare il dispatch
| 4. Gestire il fallback finale
|--------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Routing Core
// ----------------------------------------------------------------------
// router-engine.php     -> matching, validazione, dispatch finale
// middleware-loader.php -> middleware BEFORE / AFTER
// route-loader.php      -> compilazione e cache delle rotte
// ----------------------------------------------------------------------
require_once __DIR__ . '/router-engine.php';
require_once __DIR__ . '/middleware-loader.php';
require_once __DIR__ . '/route-loader.php';

// ----------------------------------------------------------------------
// Load & Compile Route Map
// Rotte dichiarative definite in routes.map.php
// ----------------------------------------------------------------------
nexi_load_routes_from_map();

// ----------------------------------------------------------------------
// Dispatch dichiarativo
// ----------------------------------------------------------------------
// Se nessuna rotta viene risolta:
// - in modalità CMS → fallback controllato
// - altrimenti → HTTP 404 secco
// ----------------------------------------------------------------------
if (!Route::dispatch_map()) {

	if (Config::get('app_mode') === 'cms') {
		require NP_ROOT . '/system/http_code/fallback.php';
	} else {
		nexi_http_response(404);
	}
}