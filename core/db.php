<?php
use Medoo\Medoo;

/**
* Restituisce l'istanza Medoo del database richiesto.
* - Multi-DB
* - Lazy loading
* - Config letta da Config (non da file)
*
* @param string $key Nome del database (default: 'default')
* @return Medoo
*/
function db(string $key = 'default'): Medoo
{
	static $connections = [];

	if (!isset($connections[$key])) {

		$dbConf = Config::get("databases.$key");

		if (!$dbConf || !is_array($dbConf)) {
			nexi_render_error(
				'Database error',
				"Database '$key' non definito in configurazione.",
				500
			);
		}

		try {
			$params = [
				'type'     => $dbConf['type'] ?? 'mariadb',
				'host'     => $dbConf['host'] ?? 'localhost',
				'database' => $dbConf['name'] ?? '',
				'username' => $dbConf['user'] ?? '',
				'password' => $dbConf['pass'] ?? '',
				'charset'  => $dbConf['charset'] ?? 'utf8mb4',
				'error'    => PDO::ERRMODE_EXCEPTION,
			];

			if (!empty($dbConf['port']))   $params['port']   = $dbConf['port'];
			if (!empty($dbConf['prefix'])) $params['prefix'] = $dbConf['prefix'];

			$connections[$key] = new Medoo($params);

		} catch (Throwable $e) {
			nexi_render_error(
				'Database connection failed',
				$e->getMessage(),
				500,
				$e->getFile(),
				$e->getLine()
			);
		}
	}

	return $connections[$key];
}