<?php
return [

// *****************************************************************************************
// Ambiente
// *****************************************************************************************

	'env'         => 'dev', // prod|stage|dev
	'app_mode' => 'cms', // cms|static

	'app_name'    => 'NexiPress CMS',
	'version'     => '0.2.50',
	'base_url'    => 'https://nexipresscms.com',
	'cookie_domain' => '.nexipresscms.com',
	'app_timezone'=> 'Europe/Rome',
	'app_timezone_fallback'=> 'Europe/Rome',

// *****************************************************************************************
// IP autorizzati
// *****************************************************************************************

'security' => [
	'ip_whitelist' => [
		'enabled' => true,
		'ips' => [
			'127.0.0.1',
			'::1',
			'185.2.145.10',
			'2001:1600:13:100:f816:3eff:fe1a:3052',
			'185.2.144.0/24',
			'2001:1600:13:100::/64',
		],
	],
],

// *****************************************************************************************
// Debug & Log
// *****************************************************************************************

	'debug'   => 'display', // display|mute
	'routing_log' => true, // false | true
	'icecube_log' => true, // false | true

	'log_dir' => NP_ROOT . '/storage/log',

// *****************************************************************************************
// Connessioni DB & Tipi
// *****************************************************************************************

	'databases' => [
		'default' => [
			'type'    => 'mariadb',
			'host'    => 'wm0aak.myd.infomaniak.com',
			'name'    => 'wm0aak_hdj478udgh209dslcm5',
			'user'    => 'wm0aak_a9dh7n4l2',
			'pass'    => 'UErD_l8N$7z_5@',
			'charset' => 'utf8mb4',
			'log'     => 'db-default',
		],
	],

	// Application Type -> NO ORM/Icecube - Use for Router, validation, input casting
	'types' => ['int','dbl','string','str','string-lower','string-upper','slug','bool','uuid'],

// *****************************************************************************************
// Middleware
// *****************************************************************************************

	'middleware_include_mode' => 'custom', // all|custom
	'middleware_include_list' => [
		'mw_AuthUser.php',
		'mw_TestAfter.php',
	],

// *****************************************************************************************
// Tema/Flags
// *****************************************************************************************

	'thm_active'  => 'default',
	'thm_fallback' => 'default', // usato se una view manca nel tema attivo

// *****************************************************************************************
// Lingua (frontend)
// *****************************************************************************************

	/*
	* mono = sito monolingua
	* prefix = sito multilingua con prefisso in url [www.namesite.com/en]
	* subdomain = con sottodominio dedicato per ogni lingua [en.namesite.com]
	*/
	'lang_mode' => 'mono',

	/*
	* home = dopo cambio lingua torna in hone
	* self = dopo cambio lingua torna nella pagina dove si e cambiata la lingua
	* url = dopo cambio lingua torna ad un URL specifico
	* silent = dopo cambio lingua non succede nulla. utile per chiamate ajax
	*/
	'lang_change' => 'self',
	'lang_default' => 'it',

	'lang_locales'  => ['en','it','es'], // Language lists available for frontend
	'lang_fallback' => 'it',

// *****************************************************************************************
// Alias
// *****************************************************************************************

	'alias_map' => [

		// Framework Core/Sys Folder
		'root:'      => '/', // Root framework
		'core:'      => 'core/', // Core framework
		'sys:'      => 'system/', // Core framework

		// Application
		'approot:'   => 'application/',
		'app:'       => 'application/controller/',
		'mid:'       => 'application/middleware/',
		'models:'    => 'application/models/',
		'lang:'      => 'application/locale/', // folder web site language frontend

		// Shared
		'shared:'    => 'application/shared/',
		'nexigrid:'  => 'application/shared/nexigrid/',
		'ngicons:'   => 'application/shared/nexigrid/icons/',

		// Themes
		'thm_root:'    => 'themes/{theme}/',
		'thm_assets:'  => 'themes/{theme}/assets/',
		'thm_components:'   => 'themes/{theme}/components/',
		'thm_pages:'   => 'themes/{theme}/pages/',
		'thm_partials:'   => 'themes/{theme}/partials/',
		'thm_plugins:' => 'themes/{theme}/plugins/',

		// Storage
		'storage:'  => 'storage/',
		'scache:'   => 'storage/cache/',
		'slog:'     => 'storage/log/',
		'stmp:'     => 'storage/temp/',
	],

// *****************************************************************************************
// Asset & API
// *****************************************************************************************

	'assets' => [
		'use_manifest' => true, // abilita cache-busting via manifest
	],
	'api_defaults' => [
		'timeout'    => 8,
		'retries'    => 1,
		'verify_ssl' => true,
	],
	'api' => [
		'customer' => [
			'url'        => 'https://api.novasoftapp.com/api/v10/clients.php',
			'api_key'    => 'key_test_public',
			'auth_key'   => 'Authorization',
			'auth_type'  => 'bearer',
			'delete_body'=> false,
		],
		'analytics' => [
			'url'      => 'https://api.stats.local/data',
			'api_key'  => 'key_analytics_xyz',
			'auth_key' => 'X-Token',
		],
	],

];