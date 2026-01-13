<?php
// Mostra solo se attivo il debug
if (X_DEBUG === 'mute') return;

$config = require X_ROOT . 'config.php';
$env = $config['env'] ?? 'prod';
// $env = 'dev';

if (!in_array($env, ['dev', 'stag'], true)) {
	nexi_render_error('Accesso non consentito', 'Il modulo env.info è disponibile solo in ambiente di sviluppo o staging.', 403);
	exit;
}
$extraDbInfo = nexi_safe_extra_databases($config);

// Parsing parametri dalla chiamata snippet()
$__params = $GLOBALS['nexi_snippet_params'] ?? [];
$showPhpInfo = !empty($__params['phpinfo']);
$declared_const = "";

foreach (array_map(
    fn($v, $k) => "$k = " . var_export($v, true),
    get_defined_constants(true)['user'] ?? [],
    array_keys(get_defined_constants(true)['user'] ?? [])
) as $line) {
    $declared_const .= $line . "<br>";
}

// === DATI ===
$debugData = [

	'SERVER' => [

		'METHOD'     => $_SERVER['REQUEST_METHOD'] ?? '-',
		'HOST'       => $_SERVER['HTTP_HOST'] ?? '-',
		'IP'         => $_SERVER['REMOTE_ADDR'] ?? '-',
		'URI'        => $_SERVER['REQUEST_URI'] ?? '-',
		'URL'        => $config['base_url'] ?? '-',

		'__sep__1' => ['separator' => true], // ← separatore visuale

		'AGENT'      => $_SERVER['HTTP_USER_AGENT'] ?? '-',
		'LANGUAGE'   => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '-',
		'ENCODING'   => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '-',
	],

	'NEXI_CONFIG' => [

		'ENV'         => X_ENV,
		'DEBUG'       => X_DEBUG,
		'APP_NAME'    => $config['app_name'] ?? '-',
		'VERSION'     => $config['version'] ?? 'ND',
		'ACTIVE_LANGS'=> $config['active_langs'] ?? '-',

		'__sep__2' => ['separator' => true], // ← separatore visuale

		'DEFAULT_TYPE'  => $config['databases']['default']['type'] ?? '-',
		'DEFAULT_HOST'  => $config['databases']['default']['host'] ?? '-',
		'DEFAULT_DB'  => $config['databases']['default']['name'] ?? '-',
		'LOG_DB'      => $config['databases']['default']['log'] ?? '-',

	],

	'EXECUTION_PHP' => [

		'MEMORY'            => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
		'TIME'              => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . ' s',
		'SESSION_ID'        => session_id(),
		'LOADED_FILES'      => count(get_included_files()) . ' files',

		'__sep__3' => ['separator' => true], // ← separatore visuale

		'LOAD_EXTENSIONS' => implode(', ', get_loaded_extensions()),

		'__sep__4' => ['separator' => true], // ← separatore visuale

		'DEF_USER_CONST' => count(get_defined_constants(true)['user'] ?? []) . ' user constants',
		'CONSTANTS' => $declared_const,
	]
];


if ($extraDbInfo !== null) {
	$debugData['NEXI_CONFIG'] += ['__sep__db' => ['separator' => true]] + $extraDbInfo;
}
?>
<link rel="stylesheet" href="/system/assets/system.css">
<div class="infobox">

	<div class="close-btn" data-target=".infobox">×</div>

	<h3>Environment Info</h3>
	<?php foreach ($debugData as $section => $items): ?>
		<h4><?= htmlspecialchars($section) ?></h4>
		<table>
		<?php foreach ($items as $key => $val): ?>
    <?php if (strpos($key, '__sep__') === 0): ?>
        <tr><td colspan="2" class="col-one"><hr></td></tr>
        <?php continue; ?>
    <?php endif; ?>

			<tr>
				<td class="col-one"><?= htmlspecialchars($key) ?></td>
				<td class="col-two"><?= is_string($val) && str_contains($val, '<br>') ? $val : htmlspecialchars((string)$val) ?></td>
			</tr>

		<?php endforeach; ?>
		</table>
	<?php endforeach; ?>

	<?php if ($showPhpInfo): ?>
		<hr><h3>phpinfo()</h3>
		<?php ob_start(); phpinfo(); $output = ob_get_clean(); echo $output; ?>
	<?php endif; ?>

</div>