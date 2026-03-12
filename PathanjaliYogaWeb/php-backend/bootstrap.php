<?php
// bootstrap.php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Database;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

Database::connect();
