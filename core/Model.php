<?php

namespace Core;

use PDO;
use PDOException;

class Model
{
    protected ?PDO $db = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
