<?php
return [

// *****************************************************************************************
// Master data Nexipress
// *****************************************************************************************

	'env' => 'dev', // prod|stage|dev
	'app_mode' => 'static', // cms|static
	'version_nexipress' => '0.2.50',
	'app_timezone'=> 'Europe/Rome',
	'app_timezone_fallback'=> 'Europe/Rome',

// *****************************************************************************************
// Authorized IP
// *****************************************************************************************

'security' => [
	'ip_whitelist' => [
		'enabled' => true,
		'ips' => [
			'127.0.0.1',
			'::1',
			// '185.2.144.10',
			// '2001:1600:13:100:f816:3eff:fe1a:3052',
			'185.2.144.0/24',
			// '2001:1600:13:100::/64',
		],
	],
],

// *****************************************************************************************
// Debug & Log
// *****************************************************************************************

	'debug'   => 'display', // display|mute

	'log_dir' => NP_ROOT . '/storage/log', // Default loggin folder
	'routing_log' => true, // false | true
	'icecube_log' => true, // false | true

// *****************************************************************************************
// Type data system
// *****************************************************************************************

	// Application Type -> NO ORM/Icecube - Use for Router, validation, input casting
	'types' => ['int','dbl','string','str','string-lower','string-upper','slug','bool','uuid'],

// *****************************************************************************************
// Middleware
// all = include all middleware
// custom = include only middleware in list middleware_include_list
// Rimangono in config.php finché non esiste un sistema dinamico di policy
// *****************************************************************************************

	'middleware_include_mode' => 'custom',
	'middleware_include_list' => [
		'mw_Before.php',
		'mw_After.php',
	],

// *****************************************************************************************
// Lingua (system)
// *****************************************************************************************

	'lang_sys_current'  => 'it', // Active Lang
	'lang_sys_list'  => 'en,it,es', // Lista lingue disponibili
	'lang_sys_fallback' => 'en', // Lingua predefinita fra quelle disponibili

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

];