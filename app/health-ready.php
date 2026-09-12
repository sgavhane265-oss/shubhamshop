<?php
require_once __DIR__ . '/RedisClient.php';

// READINESS PROBE ENDPOINT
//
// Checks everything the app actually NEEDS to serve real traffic:
//   1. Can it reach Redis? (cart won't work otherwise)
//   2. Is the product catalog present on the mounted volume?
//
// Note: when either check fails, this returns HTTP 503.
// Kubernetes then removes this Pod from the Service's endpoints -
// no traffic is routed to it - WITHOUT restarting the Pod. Once the
// dependency recovers, the next successful probe adds it back.
// Compare this behavior to health-live.php.

$checks = [];
$allOk = true;

// Check 1: Redis reachability
try {
    $r = new MiniRedis(getenv('REDIS_HOST') ?: 'redis', (int) (getenv('REDIS_PORT') ?: 6379), 1.5);
    $pong = $r->ping();
    $r->close();
    $checks['redis'] = ($pong === 'PONG') ? 'ok' : 'unexpected reply';
    if ($pong !== 'PONG') {
        $allOk = false;
    }
} catch (Exception $e) {
    $checks['redis'] = 'unreachable: ' . $e->getMessage();
    $allOk = false;
}

// Check 2: seeded product data present on the shared (hostPath/NFS-style) volume
$dataFile = __DIR__ . '/data/products.json';
if (file_exists($dataFile) && filesize($dataFile) > 0) {
    $checks['product_data'] = 'ok';
} else {
    $checks['product_data'] = 'missing at ' . $dataFile;
    $allOk = false;
}

header('Content-Type: application/json');
http_response_code($allOk ? 200 : 503);
echo json_encode([
    'status' => $allOk ? 'ready' : 'not-ready',
    'checks' => $checks,
    'pod'    => gethostname(),
    'time'   => date('c'),
]);
