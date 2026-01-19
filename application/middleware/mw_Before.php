<?php
return [
	'when' => 'before',
	'run'  => function () {

		$logDir  = X_ROOT . '/storage/log';
		$logFile = $logDir . '/middleware_test_before.log';

		if (!is_dir($logDir)) {
			mkdir($logDir, 0755, true);
		}

		file_put_contents(
			$logFile,
			"Middleware BEFORE execute\n",
			FILE_APPEND | LOCK_EX
		);
	}
];
