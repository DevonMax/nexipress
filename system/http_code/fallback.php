<?php
declare(strict_types=1);

// Stato HTTP
nexi_http_response(404);

// Config & lingue
$config   = require NP_ROOT . '/config.php';
$langs    = array_map('trim', explode(',', $config['active_langs'] ?? 'en'));
$messages = require __DIR__ . '/translate.php';

// Dati base
$title   = '404 – Not Found';
$path    = ctx::get('route.path') ?? '';
?>
<!DOCTYPE html>
<html lang="<?= ctx::get('lang.current') ?? 'en' ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="<?= alias('sys:assets/system.css') ?>">
	<title><?= $title ?></title>
</head>
<body>
	<main class="fallback">
		<h1><?= $title ?></h1>

		<?php foreach ($langs as $lang): ?>
			<?php if (!empty($messages[404][$lang])): ?>
				<p><?= htmlspecialchars($messages[404][$lang], ENT_QUOTES, 'UTF-8') ?></p>
			<?php endif; ?>
		<?php endforeach; ?>

		<p class="path">Requested path: <code><?= htmlspecialchars($path) ?></code></p>

		<?php if (Config::get('debug')): ?>
			<hr>
			<section class="debug">
				<h3><?= ctx::get('debug.title') ?></h3>
				<p><?= ctx::get('debug.message') ?></p>
				<p><small><?= ctx::get('debug.file') ?> : <?= ctx::get('debug.line') ?></small></p>
			</section>
		<?php endif; ?>
	</main>
</body>
</html>