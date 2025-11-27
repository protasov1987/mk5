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
        'updatedAt' => round(microtime(true) * 1000),
    ];
}

function build_default_data(): array
{
    $centers = [
        ['id' => gen_id('wc'), 'name' => 'Механическая обработка', 'desc' => 'Токарные и фрезерные операции', 'updatedAt' => round(microtime(true) * 1000)],
        ['id' => gen_id('wc'), 'name' => 'Покрытия / напыление', 'desc' => 'Покрытия, термическое напыление', 'updatedAt' => round(microtime(true) * 1000)],
        ['id' => gen_id('wc'), 'name' => 'Контроль качества', 'desc' => 'Измерения, контроль, визуальный осмотр', 'updatedAt' => round(microtime(true) * 1000)],
    ];

    $usedCodes = [];
    $ops = [
        ['id' => gen_id('op'), 'code' => generate_unique_op_code($usedCodes), 'name' => 'Токарная обработка', 'desc' => 'Черновая и чистовая', 'recTime' => 40, 'updatedAt' => round(microtime(true) * 1000)],
        ['id' => gen_id('op'), 'code' => generate_unique_op_code($usedCodes), 'name' => 'Напыление покрытия', 'desc' => 'HVOF / APS', 'recTime' => 60, 'updatedAt' => round(microtime(true) * 1000)],
        ['id' => gen_id('op'), 'code' => generate_unique_op_code($usedCodes), 'name' => 'Контроль размеров', 'desc' => 'Измерения, оформление протокола', 'recTime' => 20, 'updatedAt' => round(microtime(true) * 1000)],
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
        'updatedAt' => round(microtime(true) * 1000),
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
    $incoming['cards'] = merge_cards($current['cards'] ?? [], $incoming['cards'] ?? []);
    $incoming['ops'] = merge_simple_items($current['ops'] ?? [], $incoming['ops'] ?? []);
    $incoming['centers'] = merge_simple_items($current['centers'] ?? [], $incoming['centers'] ?? []);

    return $incoming;
}

function normalize_updated_at($value): int
{
    if (is_array($value) && isset($value['updatedAt'])) {
        return (int)$value['updatedAt'];
    }
    return 0;
}

function merge_simple_items(array $current, array $incoming): array
{
    $mapCurrent = [];
    foreach ($current as $item) {
        if (!isset($item['id'])) continue;
        $mapCurrent[$item['id']] = $item;
    }

    $mapIncoming = [];
    foreach ($incoming as $item) {
        if (!isset($item['id'])) continue;
        $mapIncoming[$item['id']] = $item;
    }

    $allIds = array_unique(array_merge(array_keys($mapCurrent), array_keys($mapIncoming)));
    $merged = [];
    foreach ($allIds as $id) {
        $cur = $mapCurrent[$id] ?? null;
        $inc = $mapIncoming[$id] ?? null;
        if (!$cur && $inc) { $merged[] = $inc; continue; }
        if ($cur && !$inc) { $merged[] = $cur; continue; }

        $curTs = normalize_updated_at($cur);
        $incTs = normalize_updated_at($inc);
        $base = $incTs >= $curTs ? $cur : $inc;
        $overlay = $incTs >= $curTs ? $inc : $cur;
        $merged[] = array_merge($base, $overlay);
    }
    return array_values($merged);
}

function merge_card_logs(array $currentLogs, array $incomingLogs): array
{
    $all = array_merge($currentLogs, $incomingLogs);
    $unique = [];
    foreach ($all as $log) {
        $unique[$log['id'] ?? gen_id('log')] = $log;
    }
    usort($unique, function ($a, $b) {
        return ($a['ts'] ?? 0) <=> ($b['ts'] ?? 0);
    });
    return array_values($unique);
}

function merge_card_operations(array $currentOps, array $incomingOps): array
{
    $mapCur = [];
    foreach ($currentOps as $op) {
        if (!isset($op['id'])) continue;
        $mapCur[$op['id']] = $op;
    }

    $mapInc = [];
    foreach ($incomingOps as $op) {
        if (!isset($op['id'])) continue;
        $mapInc[$op['id']] = $op;
    }

    $allIds = array_unique(array_merge(array_keys($mapCur), array_keys($mapInc)));
    $merged = [];
    foreach ($allIds as $id) {
        $cur = $mapCur[$id] ?? null;
        $inc = $mapInc[$id] ?? null;
        if (!$cur && $inc) { $merged[] = $inc; continue; }
        if ($cur && !$inc) { $merged[] = $cur; continue; }

        $curTs = normalize_updated_at($cur);
        $incTs = normalize_updated_at($inc);
        $base = $incTs >= $curTs ? $cur : $inc;
        $overlay = $incTs >= $curTs ? $inc : $cur;
        $merged[] = array_merge($base, $overlay);
    }
    return array_values($merged);
}

function merge_card_attachments(array $currentFiles, array $incomingFiles): array
{
    $mapCur = [];
    foreach ($currentFiles as $file) {
        if (!isset($file['id'])) continue;
        $mapCur[$file['id']] = $file;
    }

    $mapInc = [];
    foreach ($incomingFiles as $file) {
        if (!isset($file['id'])) continue;
        $mapInc[$file['id']] = $file;
    }

    $allIds = array_unique(array_merge(array_keys($mapCur), array_keys($mapInc)));
    $merged = [];
    foreach ($allIds as $id) {
        $cur = $mapCur[$id] ?? null;
        $inc = $mapInc[$id] ?? null;
        if (!$cur && $inc) { $merged[] = $inc; continue; }
        if ($cur && !$inc) { $merged[] = $cur; continue; }

        $curTs = normalize_updated_at($cur) ?: ($cur['createdAt'] ?? 0);
        $incTs = normalize_updated_at($inc) ?: ($inc['createdAt'] ?? 0);
        $merged[] = $incTs >= $curTs ? array_merge($cur, $inc) : array_merge($inc, $cur);
    }
    return array_values($merged);
}

function merge_cards(array $currentCards, array $incomingCards): array
{
    $mapCur = [];
    foreach ($currentCards as $card) {
        if (!isset($card['id'])) continue;
        $mapCur[$card['id']] = $card;
    }

    $mapInc = [];
    foreach ($incomingCards as $card) {
        if (!isset($card['id'])) continue;
        $mapInc[$card['id']] = $card;
    }

    $allIds = array_unique(array_merge(array_keys($mapCur), array_keys($mapInc)));
    $merged = [];
    foreach ($allIds as $id) {
        $cur = $mapCur[$id] ?? null;
        $inc = $mapInc[$id] ?? null;
        if (!$cur && $inc) { $merged[] = $inc; continue; }
        if ($cur && !$inc) { $merged[] = $cur; continue; }

        $curTs = normalize_updated_at($cur);
        $incTs = normalize_updated_at($inc);
        $base = $incTs >= $curTs ? $cur : $inc;
        $overlay = $incTs >= $curTs ? $inc : $cur;

        $combined = array_merge($base, $overlay);
        $combined['createdAt'] = $cur['createdAt'] ?? ($inc['createdAt'] ?? round(microtime(true) * 1000));
        $combined['updatedAt'] = $combined['updatedAt'] ?? max($curTs, $incTs, $combined['createdAt'] ?? round(microtime(true) * 1000));

        $combined['logs'] = merge_card_logs(
            $cur['logs'] ?? [],
            $inc['logs'] ?? []
        );

        $combined['operations'] = merge_card_operations(
            $cur['operations'] ?? [],
            $inc['operations'] ?? []
        );

        $combined['attachments'] = merge_card_attachments(
            $cur['attachments'] ?? [],
            $inc['attachments'] ?? []
        );

        if (!empty($cur['initialSnapshot'])) {
            $combined['initialSnapshot'] = $cur['initialSnapshot'];
        } elseif (!empty($inc['initialSnapshot'])) {
            $combined['initialSnapshot'] = $inc['initialSnapshot'];
        } else {
            $snapshot = deep_clone($combined);
            $snapshot['logs'] = [];
            $combined['initialSnapshot'] = $snapshot;
        }

        $merged[] = $combined;
    }

    return array_values($merged);
}

function ensure_operation_codes(array &$data): void
{
    $ops = $data['ops'] ?? [];
    $used = [];
    foreach ($ops as &$op) {
        if (empty($op['code'])) {
            $op['code'] = generate_unique_op_code($used);
        }
        if (!isset($op['updatedAt'])) {
            $op['updatedAt'] = round(microtime(true) * 1000);
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
            if (empty($operation['opCode'])) {
                $operation['opCode'] = generate_unique_op_code($used);
            }
            if (!isset($operation['updatedAt'])) {
                $operation['updatedAt'] = round(microtime(true) * 1000);
            }
            $used[] = $operation['opCode'];
        }
        unset($operation);
    }
    unset($card);
    $data['ops'] = $ops;
    $data['cards'] = $cards;
}
