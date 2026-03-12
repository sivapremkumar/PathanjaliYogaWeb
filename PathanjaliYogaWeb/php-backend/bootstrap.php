<?php
// bootstrap.php
require __DIR__ . '/vendor/autoload.php';

use App\Database;

Database::connect();
