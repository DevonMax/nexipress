<?php
/**
 * Middleware Loader – NexiPress
 * --------------------------------------------------------------------------------------
 * Questo script carica, valida e registra i middleware applicativi.
 *
 * Ruolo:
 * - Individua i file middleware da caricare
 * - Verifica la struttura di ogni middleware
 * - Registra le callback nel router (before / after)
 * - Espone informazioni di debug nel contesto (ctx)
 *
 * NON esegue i middleware.
 * NON gestisce il flusso HTTP.
 * È solo un loader/registrar.
 *
 * Ogni middleware deve restituire un array con struttura:
 * [
 *   'when' => 'before' | 'after',   // Fase di esecuzione
 *   'run'  => callable              // Funzione middleware
 * ]
 *
 * In caso di errori:
 * - l'applicazione viene bloccata
 * - viene mostrato un errore formattato
 */

// ----------------------------------------------------------------------
// Inizializzazione variabili
// ----------------------------------------------------------------------

// Path fisico della directory middleware
$middlewarePath = alias('mid:', false);

// Elenco file middleware caricati
$loaded = [];

// Tracciamento middleware registrati
$mwBefore = [];
$mwAfter  = [];

// Collezione errori middleware
$mwErrors = [];

// ----------------------------------------------------------------------
// Selezione dei middleware da caricare
// In base alla configurazione:
// - all    → tutti i file presenti nella directory
// - custom → solo quelli dichiarati in config
// ----------------------------------------------------------------------
switch (Config::get('middleware_include_mode')) {

	case 'all': // Carica TUTTI i middleware presenti nella directory

		$loaded = glob($middlewarePath . '*.php') ?: [];
		print_r($loaded);
		break;

	case 'custom': // Carica SOLO i middleware dichiarati in config

		foreach (Config::get('middleware_include_list') as $mwFile) {

			$file = alias('mid:' . $mwFile, false);

			if (is_file($file)) {
				$loaded[] = $file;
			} else {
				$mwErrors[] = [
					'file'  => $file,
					'error' => 'File declared but not found.'
				];
			}
		}
		break;
}

// ----------------------------------------------------------------------
// Analisi e registrazione dei middleware caricati
// ----------------------------------------------------------------------
foreach ($loaded as $file) {

	// Il middleware deve restituire un array
	$mw = include $file;

	// Verifica struttura di base
	if (!is_array($mw)) {
		$mwErrors[] = [
			'file'  => $file,
			'error' => 'Middleware does not return an array.'
		];
		continue;
	}

	// Verifica presenza chiavi obbligatorie
	if (!isset($mw['when'], $mw['run'])) {
		$mwErrors[] = [
			'file'  => $file,
			'error' => "Missing 'when' or 'run' keys."
		];
		continue;
	}

	// Verifica che run sia una callable valida
	if (!is_callable($mw['run'])) {
		$mwErrors[] = [
			'file'  => $file,
			'error' => "The ‘run’ field is not a valid function."
		];
		continue;
	}

	// Registrazione middleware nel router
	if ($mw['when'] === 'before') {

		Route::before($mw['run']);
		$mwBefore[] = $file;

	} elseif ($mw['when'] === 'after') {

		Route::after($mw['run']);
		$mwAfter[] = $file;

	} else {

		$mwErrors[] = [
			'file'  => $file,
			'error' => "Invalid 'when' value: {$mw['when']}"
		];
		continue;
	}
}

// ----------------------------------------------------------------------
// Salvataggio informazioni nel contesto (ctx)
// Utile per debug, diagnostica e tooling
// ----------------------------------------------------------------------
ctx::set('middleware.before', $mwBefore);
ctx::set('middleware.after',  $mwAfter);
ctx::set('middleware.errors', $mwErrors);

// ----------------------------------------------------------------------
// Gestione errori middleware
// Se presenti errori → blocca l'applicazione
// ----------------------------------------------------------------------
if (!empty($mwErrors)) {

	$formatted = array_map(function ($e) {
		return $e['file'] . '. ' . $e['error'];
	}, $mwErrors);

	$final = implode("\n", $formatted);

	nexi_render_error_safe(
		nexi_lang('mdware_title'),
		nexi_lang('mdware_message', $final),
		500
	);
	exit;
}