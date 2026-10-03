<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/i18n.php';

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

define('CHAT_MASTER_KEY', hex2bin($_ENV['CHAT_MASTER_KEY_HEX'] ?? ''));

ini_set('display_errors', 0);

use Parse\ParseClient;
use Parse\ParseUser;
use Parse\ParseSessionStorage;

ParseClient::initialize(
    $_ENV['PARSE_APP_ID'] ?? '',
    $_ENV['PARSE_REST_KEY'] ?? '',
    $_ENV['PARSE_MASTER_KEY'] ?? ''
);
ParseClient::setServerURL($_ENV['PARSE_SERVER_URL'] ?? 'https://parseapi.back4app.com/', '/');
ParseClient::setConnectionTimeout(15);
ParseClient::setTimeout(45);
ParseClient::setStorage(new ParseSessionStorage());

$currentUser = ParseUser::getCurrentUser();

if ($currentUser) {
    setcookie(
        'parse_session',
        $currentUser->getSessionToken(),
        [
            'expires' => time() + 86400,
            'path' => '/',
            'secure' => filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

if ($currentUser && !isset($_SESSION['favorites'])) {
    try {
        $favQuery = $currentUser->getRelation("favorites")->getQuery();
        $favQuery->limit(100);
        $favorites = parseRetry(function () use ($favQuery) {
            return $favQuery->find();
        });
        foreach ($favorites as $f) {
            $_SESSION['favorites'][$f->getObjectId()] = true;
        }
    } catch (Exception $e) {
        // Связь favorites может быть не настроена — не критично
    }
}
