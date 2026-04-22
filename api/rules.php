<?php
/**
 * API: Rules CRUD
 * GET    /api/rules.php           — List all rules
 * POST   /api/rules.php           — Create a rule
 * PUT    /api/rules.php?id=X      — Update a rule
 * DELETE /api/rules.php?id=X      — Delete a rule
 * PATCH  /api/rules.php?id=X      — Toggle rule active status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../engine/RuleEngine.php';

$ruleEngine = new RuleEngine();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $rule = $ruleEngine->getRule(intval($_GET['id']));
                if ($rule) {
                    echo json_encode(['success' => true, 'rule' => $rule]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => true, 'message' => 'Rule not found']);
                }
            } else {
                $rules = $ruleEngine->getAllRules();
                $categories = $ruleEngine->getCategories();
                echo json_encode([
                    'success' => true, 
                    'rules' => $rules, 
                    'categories' => array_values($categories)
                ]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['name']) || empty($input['pattern'])) {
                http_response_code(400);
                echo json_encode(['error' => true, 'message' => 'Name and pattern are required']);
                exit;
            }
            $id = $ruleEngine->addRule($input);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Rule created']);
            break;

        case 'PUT':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => true, 'message' => 'Rule ID required']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $ruleEngine->updateRule($id, $input);
            echo json_encode(['success' => true, 'message' => 'Rule updated']);
            break;

        case 'PATCH':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => true, 'message' => 'Rule ID required']);
                exit;
            }
            $ruleEngine->toggleRule($id);
            echo json_encode(['success' => true, 'message' => 'Rule toggled']);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => true, 'message' => 'Rule ID required']);
                exit;
            }
            $ruleEngine->deleteRule($id);
            echo json_encode(['success' => true, 'message' => 'Rule deleted']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
