<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_COOKIE['parse_session'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

echo json_encode([
    'sessionToken' => $_COOKIE['parse_session'],
    'parse_app_id' => $_ENV['PARSE_APP_ID'] ?? '',
    'parse_js_key' => $_ENV['PARSE_JS_KEY'] ?? '',
    'parse_url' => $_ENV['PARSE_LIVEQUERY_URL'] ?? ''
]);
