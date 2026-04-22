<?php
/**
 * API: Logs retrieval
 * GET /api/logs.php              — List logs with filters
 * GET /api/logs.php?id=X         — Get single log detail
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../engine/AnalysisEngine.php';

try {
    $engine = new AnalysisEngine();

    if (isset($_GET['id'])) {
        $log = $engine->getLogDetail(intval($_GET['id']));
        if ($log) {
            echo json_encode(['success' => true, 'log' => $log]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Log not found']);
        }
    } else {
        $filters = [
            'verdict' => $_GET['verdict'] ?? '',
            'category' => $_GET['category'] ?? '',
            'search' => $_GET['search'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'risk_min' => $_GET['risk_min'] ?? '',
            'limit' => $_GET['limit'] ?? 100,
            'offset' => $_GET['offset'] ?? 0,
        ];
        $result = $engine->getLogs($filters);
        echo json_encode(['success' => true] + $result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
