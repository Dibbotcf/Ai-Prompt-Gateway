<?php
/**
 * API: Dashboard Statistics
 * GET /api/stats.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../engine/AnalysisEngine.php';

try {
    $engine = new AnalysisEngine();
    $stats = $engine->getStats();
    echo json_encode(['success' => true, 'stats' => $stats]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
