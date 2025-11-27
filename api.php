<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$currentUser = require_login($pdo);

try {
    if ($method === 'GET') {
        ensure_permission($currentUser, 'cards', 'view');
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
        ensure_permission($currentUser, 'cards', 'edit');
        $current = fetch_state($pdo);
        $incoming = [
            'cards' => $payload['cards'] ?? [],
            'ops' => $payload['ops'] ?? [],
            'centers' => $payload['centers'] ?? [],
        ];
        $incoming = merge_snapshots($current, $incoming);
        ensure_operation_codes($incoming);
        if (empty($currentUser['permissions']['attachments']['edit'])) {
            $currentCards = $current['cards'] ?? [];
            foreach ($incoming['cards'] as &$card) {
                foreach ($currentCards as $existing) {
                    if (($existing['id'] ?? null) === ($card['id'] ?? null)) {
                        $card['attachments'] = $existing['attachments'] ?? [];
                        break;
                    }
                }
            }
            unset($card);
        }
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
