<?php
require __DIR__ . '/../vendor/autoload.php';

use WakeOnStorage\Api;

header('Content-Type: application/json');

$app = new Api();
$app->run();
