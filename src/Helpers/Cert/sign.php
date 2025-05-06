<?php
namespace MpSoft\MpDocumentPrint\Cert;

require_once __DIR__ . '/SignHelper.php';

header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['toSign'])) {
    http_response_code(400);
    echo 'Missing toSign';
    exit;
}

try {
    $signature = SignHelper::sign($input['toSign']);
    echo $signature;
} catch (\Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}
