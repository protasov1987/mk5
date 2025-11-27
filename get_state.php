<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $currentUser = require_login($pdo);
    ensure_permission($currentUser, 'cards', 'view');

    $state = fetch_state($pdo);
    ensure_operation_codes($state);

    echo json_encode($state, JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
