<?php
declare(strict_types=1);

// Ping CLI NexiPress — core + IP pubblico

function get_public_ip(): string
{
	$services = [
		'https://api.ipify.org',
		'https://ifconfig.me/ip',
		'https://icanhazip.com',
	];

	foreach ($services as $url) {
		$ip = @file_get_contents($url);
		if ($ip !== false) {
			$ip = trim($ip);
			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				return $ip;
			}
		}
	}

	return 'unknown';
}

$ip = get_public_ip();

echo "OK: ping\n";
echo "CLI responding: YES\n";
echo "Public IP: {$ip}\n";

return 0;
