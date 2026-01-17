<?php
require __DIR__ . '/vendor/autoload.php';

use NexiPress\orm\Icecube;

$cfg = [
    'dsn' => 'sqlite::memory:',
];

$icecube = new Icecube($cfg);

var_dump(get_class($icecube));
