<?php

// File: /application/routes.map.php
// Mappa centrale delle rotte dichiarative Nexipress
// Ogni rotta ha: un alias obbligatorio, una route con parametri inline, e un file target controller

return [

	'@home' => [
		'route'  => 'home',
		'target' => 'home.controller',
		'required' => false
	],

	'@lang_change' => [
		'route'    => 'change-language/:lang',
		'target'   => 'sys:locale/_lang_switch.controller',
		'required' => true
	],

	'@articles_list' => [
		'route'    => 'article/:category(string)/:slug(string)',
		'target'   => 'article.controller',
		'required' => true
	],

];