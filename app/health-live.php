<?php
// LIVENESS PROBE ENDPOINT
//
// Deliberately checks NOTHING except "can PHP execute and respond?"
// It must NOT check Redis, the PVC, or any other dependency.
//
// Note: if this endpoint checked Redis and Redis went down,
// Kubernetes would start KILLING AND RESTARTING this pod over and
// over - which does nothing to fix Redis and just adds churn. Liveness
// answers "is this process broken and needs a restart?", not "is
// everything this process depends on healthy?". That's readiness's job.
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status' => 'alive',
    'pod'    => gethostname(),
    'time'   => date('c'),
]);
