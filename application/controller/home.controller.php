<?php
$checks = [
	'config'  => file_exists(X_ROOT . '/config.php'),
	'storage' => is_writable(X_ROOT . '/storage'),
];
// nexi_debug_env();
$title = "";
$description = 'NexiPress è il CMS moderno e modulare pensato per sviluppatori e creatori di contenuti. Veloce, flessibile e completamente personalizzabile, unisce controller, template e componenti in un sistema semplice e potente, con Orbit per costruire pagine in modo visuale quando serve';
$author = 'Claudio Pistidda';
$keywords = 'nexipress, cms moderno, cms php, orbit editor, tema personalizzabile, sviluppo web, template modulari, framework leggero, creare siti, componenti php, template engine, nexipress cms';

view('home', [
	'title' => $title,
	'description'  => $description,
	'author' => $author,
	'keywords' => $keywords,
	'checks' => $checks
]);
?>
