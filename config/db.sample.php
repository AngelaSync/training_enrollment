<?php

require_once __DIR__ . '/../classes/Database.php';

$dsn  = "mysql:host=localhost;dbname=training_db;charset=utf8mb4";
$user = "your_db_username";
$pass = "your_db_password";

$db = Database::getInstance($dsn, $user, $pass);