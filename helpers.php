<?php
require_once __DIR__ . '/config.php';

function gen_id(string $prefix): string
{
    return $prefix . '_' . base_convert((string)microtime(true), 10, 36) . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function compute_ean13_check_digit(string $base12): string
{
    $sumEven = 0;
    $sumOdd = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int)$base12[$i];
        if (($i + 1) % 2 === 0) {
            $sumEven += $digit;
        } else {
            $sumOdd += $digit;
        }
    }
    $total = $sumOdd + $sumEven * 3;
    $mod = $total % 10;
    return (string)((10 - $mod) % 10);
}

function generate_ean13(): string
{
    $base = '';
    for ($i = 0; $i < 12; $i++) {
        $base .= random_int(0, 9);
    }
    return $base . compute_ean13_check_digit($base);
}

function generate_unique_ean13(array $cards): string
{
    $attempt = 0;
    while ($attempt < 500) {
        $code = generate_ean13();
        foreach ($cards as $card) {
            if (($card['barcode'] ?? '') === $code) {
                continue 2;
            }
        }
        return $code;
    }
    return generate_ean13();
}

function generate_raw_op_code(): string
{
    return 'OP-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
}

function generate_unique_op_code(array $used): string
{
    $code = generate_raw_op_code();
    $attempt = 0;
    while (in_array($code, $used, true) && $attempt < 1000) {
        $code = generate_raw_op_code();
        $attempt++;
    }
    return $code;
}

function create_route_op_from_refs(array $op, array $center, string $executor = '', int $plannedMinutes = 30, int $order = 1): array
{
    return [
        'id' => gen_id('rop'),
        'opId' => $op['id'] ?? null,
        'opCode' => $op['code'] ?? generate_raw_op_code(),
        'opName' => $op['name'] ?? 'Операция',
        'centerId' => $center['id'] ?? null,
        'centerName' => $center['name'] ?? '',
        'executor' => $executor,
        'plannedMinutes' => $plannedMinutes,
        'status' => 'NOT_STARTED',
        'firstStartedAt' => null,
        'startedAt' => null,
        'lastPausedAt' => null,
        'finishedAt' => null,
        'actualSeconds' => null,
        'elapsedSeconds' => 0,
        'order' => $order,
        'comment' => '',
        'goodCount' => 0,
        'scrapCount' => 0,
        'holdCount' => 0,
    ];
}

function build_default_data(): array
{
    $centers = [
        ['id' => gen_id('wc'), 'name' => 'Механическая обработка', 'desc' => 'Токарные и фрезерные операции'],
        ['id' => gen_id('wc'), 'name' => 'Покрытия / напыление', 'desc' => 'Покрытия, термическое напыление'],
        ['id' => gen_id('wc'), 'name' => 'Контроль качества', 'desc' => 'Измерения, контроль, визуальный осмотр'],
    ];

    $usedCodes = [];
    $ops = [
        ['id' => gen_id('op'), 'code' => generate_unique_op_code($usedCodes), 'name' => 'Токарная обработка', 'desc' => 'Черновая и чистовая', 'recTime' => 40],
        ['id' => gen_id('op'), 'code' => generate_unique_op_code($usedCodes), 'name' => 'Напыление покрытия', 'desc' => 'HVOF / APS', 'recTime' => 60],
        ['id' => gen_id('op'), 'code' => generate_unique_op_code($usedCodes), 'name' => 'Контроль размеров', 'desc' => 'Измерения, оформление протокола', 'recTime' => 20],
    ];

    $cardId = gen_id('card');
    $cards = [[
        'id' => $cardId,
        'barcode' => generate_unique_ean13([]),
        'name' => 'Вал привода Ø60',
        'orderNo' => 'DEMO-001',
        'desc' => 'Демонстрационная карта для примера.',
        'quantity' => 1,
        'drawing' => 'DWG-001',
        'material' => 'Сталь',
        'status' => 'NOT_STARTED',
        'archived' => false,
        'createdAt' => round(microtime(true) * 1000),
        'logs' => [],
        'initialSnapshot' => null,
        'attachments' => [],
        'operations' => [
            create_route_op_from_refs($ops[0], $centers[0], 'Иванов И.И.', 40, 1),
            create_route_op_from_refs($ops[1], $centers[1], 'Петров П.П.', 60, 2),
            create_route_op_from_refs($ops[2], $centers[2], 'Сидоров С.С.', 20, 3),
        ],
    ]];

    return ['cards' => $cards, 'ops' => $ops, 'centers' => $centers];
}

function deep_clone($value)
{
    return json_decode(json_encode($value, JSON_UNESCAPED_UNICODE), true);
}

function merge_snapshots(array $current, array $incoming): array
{
    $existingCards = $current['cards'] ?? [];
    $incomingCards = $incoming['cards'] ?? [];

    $mergedCards = array_map(function ($card) use ($existingCards) {
        $existing = null;
        foreach ($existingCards as $c) {
            if (($c['id'] ?? null) === ($card['id'] ?? null)) {
                $existing = $c;
                break;
            }
        }
        $next = deep_clone($card);
        $next['createdAt'] = $existing['createdAt'] ?? ($next['createdAt'] ?? round(microtime(true) * 1000));
        if (!isset($next['logs']) || !is_array($next['logs'])) {
            $next['logs'] = [];
        }
        if ($existing && !empty($existing['initialSnapshot'])) {
            $next['initialSnapshot'] = $existing['initialSnapshot'];
        } elseif (empty($next['initialSnapshot'])) {
            $snapshot = deep_clone($next);
            $snapshot['logs'] = [];
            $next['initialSnapshot'] = $snapshot;
        }
        return $next;
    }, $incomingCards);

    $incoming['cards'] = $mergedCards;
    return $incoming;
}

function ensure_operation_codes(array &$data): void
{
    $ops = $data['ops'] ?? [];
    $used = [];
    foreach ($ops as &$op) {
        if (empty($op['code'])) {
            $op['code'] = generate_unique_op_code($used);
        }
        $used[] = $op['code'];
    }
    unset($op);

    $opMap = [];
    foreach ($ops as $op) {
        $opMap[$op['id']] = $op;
    }
    $cards = $data['cards'] ?? [];
    foreach ($cards as &$card) {
        if (!isset($card['operations']) || !is_array($card['operations'])) {
            $card['operations'] = [];
        }
        foreach ($card['operations'] as &$operation) {
            $source = $operation['opId'] ?? null;
            if ($source && isset($opMap[$source]['code'])) {
                $operation['opCode'] = $opMap[$source]['code'];
            }
            if (empty($operation['opCode']) || in_array($operation['opCode'], $used, true)) {
                $operation['opCode'] = generate_unique_op_code($used);
            }
            $used[] = $operation['opCode'];
        }
        unset($operation);
    }
    unset($card);
    $data['ops'] = $ops;
    $data['cards'] = $cards;
}
