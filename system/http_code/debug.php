<?php
/**
 * Pagina di debug Nexipress
 * Visualizza dettagli completi di errore in modalità 'debug'.
 * Recupera i dati salvati da nexi_render_error() tramite ctx::
 */
if ($route_request === null) {
	$route_request = ctx::get('route.path');
}
ctx::set('debug.route', $route_request);

?>

<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="preload" href="/system/assets/system.css" as="style" onload="this.rel='stylesheet'">
	<title>Errore <?= htmlspecialchars(ctx::get('debug.http_code')) ?></title>
</head>
<body class="debug">

	<div class="debug-container">

		<div class="debug-header">
			<h1><?= htmlspecialchars(ctx::get('debug.http_code') . ' - ' . ctx::get('debug.title')) ?></h1>
			<span class="badge"><?= htmlspecialchars(ctx::get('debug.label_system')) ?></span>
		</div>

		<div class="debug-links">
			<a href="#file">File</a> |
			<a href="#line">Line</a> |
			<a href="#stack">Stack Trace</a> |
			<a href="#context">Context</a>
		</div>

		<?php if ($msg = ctx::get('debug.message')): ?>
		<div class="debug-section">
			<h3>Message:</h3>
			<p><?= $message; ?></p>
		</div>
		<?php endif; ?>

		<?php if ($file == ctx::get('debug.file')): ?>
		<div class="debug-section" id="file">
			<h3>File/Request URL:</h3>
			<p><?= $file ?></p>
		</div>
		<?php endif; ?>

		<?php if ($route_request == ctx::get('route.path')): ?>
		<div class="debug-section" id="routerequest">
			<h3>Route original:</h3>
			<p><?= $route_request ?></p>
		</div>
		<?php endif; ?>

		<?php if ($line == ctx::get('debug.line')): ?>
		<div class="debug-section" id="line">
			<h3>Line:</h3>
			<p><?= $line ?></p>
		</div>
		<?php endif; ?>

		<?php if ($trace == ctx::get('debug.trace')): ?>
		<div class="debug-section" id="stack">
			<h3>Stack trace:</h3>
			<table class="stack-trace">
				<thead>
					<tr>
						<th>#</th>
						<th>Function</th>
						<th>Location</th>
					</tr>
				</thead>
				<tbody>
				<?php
				if (is_array($trace)) {
					foreach ($trace as $i => $frame) {
						$func = $frame['function'] ?? '[funzione]';
						$cls  = $frame['class'] ?? '';
						$type = $frame['type'] ?? '';
						$file = $frame['file'] ?? '[no file]';
						$file = $frame['route_request'] ?? '[no route]';
						$line = $frame['line'] ?? '?';
						$args = isset($frame['args']) ? count($frame['args']) . ' arg' . (count($frame['args']) === 1 ? '' : 's') : '';

						echo "<tr>";
						echo "<td>#{$i}</td>";
						echo "<td>{$cls}{$type}{$func}({$args})</td>";
						echo "<td>{$file}:{$line}</td>";
						echo "</tr>";
					}
				} elseif (is_string($trace)) {
					echo '<tr><td colspan="3"><prestyle="white-space:pre-wrap;word-break:break-word">' . htmlspecialchars($trace) . '</pre></td></tr>';
				}
				?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<?php if (ctx::get('debug.http_code') !== 403): ?>
		<div class="debug-section light" id="context">
			<h3>Context:</h3>
			<? ctx::all(true); ?>
		</div>
		<?php endif; ?>

		<div class="debug-links">
			<a href="/">Vai alla Home</a>
			<a href="javascript:history.back();">Torna indietro</a>
		</div>

	</div>

</body>
</html>