<?php
return [
	'when' => 'before',
	'run' => function () {

		$routes = require alias('scache:routes.cache.php', false);

		$uri = '/' . ltrim(
			parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
			'/'
		);

		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		foreach ($routes as $route) {

			// method check
			if (
				($route['method'] ?? 'GET') !== 'ANY'
				&& ($route['method'] ?? 'GET') !== $method
			) {
				continue;
			}

			// pattern match
			if (!preg_match($route['pattern'], $uri)) {
				continue;
			}

			// rotta trovata → applico policy solo se richiesto
			if (($route['meta']['ip_whitelist'] ?? false) === true) {

				$allowed = Config::get('allowed_ips') ?? [];
				$clientIp = $_SERVER['REMOTE_ADDR'] ?? null;

				if (!$clientIp || !ip_in_allowed($allowed, $clientIp)) {
					http_response_code(403);
					nexi_render_error(
						'Forbidden',
						'Accesso non autorizzato.',
						403
					);
				}
			}

			break; // usa SOLO la prima rotta matchata
		}
	}
];
