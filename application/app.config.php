<?php
return [

// *****************************************************************************************
// Master data application
// *****************************************************************************************

	'app_name'    => 'Cral Amga',
	'version'     => '1.0.0',
	'base_url'    => 'https://tempoliberoamga.app',
	'cookie_domain' => '.tempoliberoamga.com',

// *****************************************************************************************
// Connection DB
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
// Asset
// *****************************************************************************************

	'assets' => [
		'use_manifest' => true, // abilita cache-busting via manifest
	],

// *****************************************************************************************
// API
// *****************************************************************************************

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