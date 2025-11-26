<?php
require_once __DIR__ . '/storage.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    http_response_code(400);
    echo 'Missing file id';
    exit;
}

$data = fetch_state($pdo);
$attachment = null;
foreach ($data['cards'] ?? [] as $card) {
    foreach ($card['attachments'] ?? [] as $file) {
        if (($file['id'] ?? '') === $id) {
            $attachment = $file;
            break 2;
        }
    }
}

if (!$attachment || empty($attachment['content'])) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

$base64 = explode(',', (string)$attachment['content']);
$payload = end($base64);
$binary = base64_decode($payload);
if ($binary === false) {
    http_response_code(500);
    echo 'Corrupted file';
    exit;
}

$filename = $attachment['name'] ?? 'file';
$type = $attachment['type'] ?? 'application/octet-stream';
header('Content-Type: ' . $type);
header('Content-Length: ' . strlen($binary));
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $binary;
