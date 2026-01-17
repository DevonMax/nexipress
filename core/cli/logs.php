<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| NexiPress CLI — logs command
|--------------------------------------------------------------------------
| Uso:
|   php cli/nexi logs
|   php cli/nexi logs <file>
|   php cli/nexi logs <file> <lines>
|   php cli/nexi logs <file> --grep ERROR
|   php cli/nexi logs <file> --since 1h
|
| Presupposti:
| - Log in formato CSV
| - Prima colonna: TIMESTAMP (Y-m-d H:i:s)
| - Seconda colonna: STATUS (SUCCESS|WARNING|ERROR)
|--------------------------------------------------------------------------
*/

return function (array $argv): int {

	$logDir = rtrim(Config::get('log_dir'), '/');
	if (!is_dir($logDir)) {
		echo "No log directory found.\n";
		return 1;
	}

	// -----------------------------
	// Args parsing minimale
	// -----------------------------
	$file  = $argv[2] ?? null;
	$lines = 10;
	$grep  = null;
	$since = null;

	foreach ($argv as $arg) {
		if (str_starts_with($arg, '--grep=')) {
			$grep = substr($arg, 7);
		}
		if (str_starts_with($arg, '--since=')) {
			$since = substr($arg, 8);
		}
	}

	if (isset($argv[3]) && ctype_digit($argv[3])) {
		$lines = (int)$argv[3];
	}

	// -----------------------------
	// Nessun file → lista log
	// -----------------------------
	if ($file === null) {
		echo "Available logs:\n";
		foreach (glob($logDir . '/*.log') as $log) {
			echo "- " . basename($log) . "\n";
		}
		return 0;
	}

	$logFile = $logDir . '/' . $file;
	if (!file_exists($logFile)) {
		echo "Log file not found: {$file}\n";
		return 1;
	}

	// -----------------------------
	// Since parsing (1h, 24h, 7d)
	// -----------------------------
	$sinceTs = null;
	if ($since) {
		if (preg_match('/^(\d+)(h|d)$/', $since, $m)) {
			$sinceTs = time() - ($m[2] === 'h'
				? ((int)$m[1] * 3600)
				: ((int)$m[1] * 86400)
			);
		}
	}

	// -----------------------------
	// Lettura CSV (dal fondo)
	// -----------------------------
	$rows = [];
	if (($fp = fopen($logFile, 'r')) === false) {
		echo "Cannot open log file.\n";
		return 1;
	}

	$header = fgetcsv($fp); // salta header

	while (($row = fgetcsv($fp)) !== false) {
		$rows[] = $row;
	}
	fclose($fp);

	$rows = array_reverse($rows);

	$out = [];
	foreach ($rows as $row) {

		$timestamp = strtotime($row[0] ?? '');
		$status    = $row[1] ?? '';

		if ($sinceTs && ($timestamp === false || $timestamp < $sinceTs)) {
			continue;
		}

		if ($grep && stripos($status, $grep) === false) {
			continue;
		}

		$out[] = $row;
		if (count($out) >= $lines) {
			break;
		}
	}

	if (!$out) {
		echo "No matching log entries.\n";
		return 0;
	}

	// -----------------------------
	// Output
	// -----------------------------
	echo "Showing last " . count($out) . " entries from {$file}\n\n";
	foreach (array_reverse($out) as $r) {
		echo implode(' | ', $r) . "\n";
	}

	return 0;
};
