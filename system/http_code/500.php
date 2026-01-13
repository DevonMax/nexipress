<?php
$config = require NP_ROOT . '/config.php';
$langs = explode(',', $config['active_langs'] ?? 'en');
$messages = require __DIR__ . '/translate.php';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="./system/assets/system.css" rel="stylesheet">
	<title>500 - Internal Server Error</title>
</head>
<body class="debug">
	<div class="debug-container">
		<h1 class="debug-header">500 - Internal Server Error</h1>
		<?php
		foreach ($langs as $lang) {
			$lang = trim($lang);
			$msg = $messages[500][$lang] ?? '';
			$tmpPath = alias('sys:locale/flags/'.$lang.'.jpg');
			echo '<p><img src="'.$tmpPath.'" width="15"> '. $msg . '</p>';
		?>
		<div class="debug-section">
			<?php
				echo "<h3>".ctx::get('debug.title')."</h3>";
				echo "<p><strong>Message</strong>: ".ctx::get('debug.message')."</p>";
				echo "<p><strong>File</strong>: ".ctx::get('debug.file')."</p>";
				echo "<p><strong>Line</strong>: ".ctx::get('debug.line')."</p>";
			}
			?>
			<div class="debug-section light"><?php ctx_dump(); ?></div>
		</div>
	</div>
</body>
</html>