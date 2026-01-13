<?php

// lingua richiesta dalla route
$lang = trim(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

$supported = Config::get('lang_locales');
$fallback  = Config::get('lang_fallback');
$mode      = Config::get('lang_change', 'home');

// validazione lingua
if (!$lang || !in_array($lang, $supported, true)) {
	$lang = $fallback;
}

// persistenza lingua
$_SESSION['locale'] = $lang;
setcookie('locale', $lang, time() + 86400 * 30, '/');

// comportamento post cambio lingua
switch ($mode) {

	case 'silent':
		http_response_code(204);
		exit;

case 'self':
	$ref = $_SERVER['HTTP_REFERER'] ?? '/';

	$parts = parse_url($ref);
	$path  = trim($parts['path'] ?? '', '/');
	$query = $parts['query'] ?? '';

	$segments = $path !== '' ? explode('/', $path) : [];

	// se il primo segmento è una lingua, sostituiscilo
	if (in_array($segments[0] ?? '', Config::get('lang_locales'), true)) {
		$segments[0] = $lang;
	} else {
		array_unshift($segments, $lang);
	}

	$newPath = '/' . implode('/', $segments);
	if ($query !== '') {
		$newPath .= '?' . $query;
	}

	header('Location: ' . $newPath, true, 302);
	exit;



	case 'url':
		$target = Config::get('lang_change_url', '/');
		header('Location: ' . $target, true, 302);
		exit;

	case 'home':
	default:
		// ctx::set('lang.current', $lang);
		header('Location: /', true, 302);
		exit;
}