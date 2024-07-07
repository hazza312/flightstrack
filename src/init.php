<?php

require_once(__DIR__ . '../../vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__. '/../');
$dotenv->load();

$db = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);

if (PHP_SAPI !== 'cli') {
    session_start();
    if (!isset($_SESSION['flightsid'])) {
        $user = $_SERVER['REMOTE_USER'];

        $st = $db->prepare("SELECT id FROM user WHERE user.user = ?"); 
        $st->execute([$user]);
        $lookup = $st->fetch();

        if (!isset($lookup['id'])) {
            http_response_code(403);
            exit();
        }

        $_SESSION['flightsuser'] = $user;
        $_SESSION['flightsid'] = $lookup['id'];
    }
}
?>