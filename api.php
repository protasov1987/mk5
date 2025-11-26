<?php
require_once __DIR__ . '/storage.php';

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $state = fetch_state($pdo);
        ensure_operation_codes($state);
        echo json_encode($state, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['error' => 'Некорректный JSON']);
            exit;
        }
        $current = fetch_state($pdo);
        $incoming = [
            'cards' => $payload['cards'] ?? [],
            'ops' => $payload['ops'] ?? [],
            'centers' => $payload['centers'] ?? [],
        ];
        $incoming = merge_snapshots($current, $incoming);
        ensure_operation_codes($incoming);
        save_state($pdo, $incoming);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
