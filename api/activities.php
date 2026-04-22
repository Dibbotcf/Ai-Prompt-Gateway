<?php
/**
 * API: Activities CRUD
 * POST   /api/activities.php             — Create activity
 * GET    /api/activities.php             — List activities
 * GET    /api/activities.php?id=X        — Get single activity with prompts
 * PUT    /api/activities.php?id=X        — Update/close activity
 * DELETE /api/activities.php?id=X        — Delete activity
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single activity with its prompts
                $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
                $stmt->execute([$id]);
                $activity = $stmt->fetch();

                if (!$activity) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Activity not found']);
                    exit;
                }

                // Get prompts for this activity
                $stmt = $pdo->prepare("
                    SELECT ap.*, pl.matched_rules_count, pl.categories_matched, pl.analysis_time_ms
                    FROM activity_prompts ap
                    LEFT JOIN prompt_logs pl ON ap.log_id = pl.id
                    WHERE ap.activity_id = ?
                    ORDER BY ap.sequence_order ASC
                ");
                $stmt->execute([$id]);
                $activity['prompts'] = $stmt->fetchAll();

                echo json_encode(['success' => true, 'activity' => $activity]);
            } else {
                // List all activities
                $status = $_GET['status'] ?? '';
                $limit = intval($_GET['limit'] ?? 50);
                $offset = intval($_GET['offset'] ?? 0);

                $where = '1=1';
                $params = [];
                if ($status) {
                    $where .= " AND status = ?";
                    $params[] = $status;
                }

                $stmt = $pdo->prepare("
                    SELECT * FROM activities 
                    WHERE $where 
                    ORDER BY updated_at DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $activities = $stmt->fetchAll();

                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE $where");
                $countStmt->execute($params);
                $total = intval($countStmt->fetchColumn());

                echo json_encode(['success' => true, 'activities' => $activities, 'total' => $total]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Activity name is required']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO activities (name, description, user_model, destination_model, status)
                VALUES (?, ?, ?, ?, 'open')
            ");
            $stmt->execute([
                $input['name'],
                $input['description'] ?? null,
                $input['user_model'] ?? 'custom',
                $input['destination_model'] ?? 'simulated',
            ]);

            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
            $stmt->execute([$newId]);

            echo json_encode(['success' => true, 'activity' => $stmt->fetch(), 'message' => 'Activity created']);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Activity ID required']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? 'update';

            if ($action === 'close') {
                // Close the activity — recalculate stats
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total, 
                           SUM(CASE WHEN verdict = 'blocked' THEN 1 ELSE 0 END) as blocked,
                           COALESCE(AVG(risk_score), 0) as avg_risk
                    FROM activity_prompts WHERE activity_id = ?
                ");
                $stmt->execute([$id]);
                $stats = $stmt->fetch();

                $stmt = $pdo->prepare("
                    UPDATE activities 
                    SET status = 'closed', closed_at = NOW(), 
                        total_prompts = ?, blocked_prompts = ?, avg_risk_score = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $stats['total'], $stats['blocked'], $stats['avg_risk'], $id
                ]);
            } else {
                // General update
                $fields = [];
                $values = [];
                foreach (['name', 'description', 'user_model', 'destination_model'] as $field) {
                    if (isset($input[$field])) {
                        $fields[] = "$field = ?";
                        $values[] = $input[$field];
                    }
                }
                if (!empty($fields)) {
                    $values[] = $id;
                    $stmt = $pdo->prepare("UPDATE activities SET " . implode(', ', $fields) . " WHERE id = ?");
                    $stmt->execute($values);
                }
            }

            $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'activity' => $stmt->fetch()]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Activity ID required']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Activity deleted']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
