<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $currentUser = require_login($pdo);
    ensure_permission($currentUser, 'cards', 'edit');

    $payload = get_json_payload();
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректные данные']);
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
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
