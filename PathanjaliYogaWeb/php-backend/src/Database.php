<?php
// src/Database.php
namespace App;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database {
    public static function connect() {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => getenv('DB_HOST') ?: 'localhost',
            'database'  => getenv('DB_DATABASE') ?: 'yogatrust',
            'username'  => getenv('DB_USERNAME') ?: 'root',
            'password'  => getenv('DB_PASSWORD') ?: '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
