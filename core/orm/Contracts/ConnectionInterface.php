<?php

namespace NexiPress\orm\Contracts;

use PDO;

interface ConnectionInterface
{
    public function pdo(): PDO;
}