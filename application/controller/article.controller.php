<?php
$title = 'Article controller';
$description = 'description';
$author = 'author';
$keywords = 'keywords';

$article = [
	'id' => 1,
	'category' => [
		'it' => 'tecnologia',
		'en' => 'tech',
		'es' => 'tecnologia',
	],
	'slug' => [
		'it' => 'creare-una-rete-casalinga',
		'en' => 'create-a-home-network',
		'es' => 'crear-una-red-domestica',
	],
];

view('articles/article.list', [
	'title' => $title,
	'description'  => $description,
	'author' => $author,
	'keywords' => $keywords,
	'article' => $article
]);