<?php
/**
 * API: Analyze a prompt (v2 — with activity & AI model support)
 * POST /api/analyze.php
 * Body: { "prompt", "source", "activity_id", "destination_model" }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../engine/AnalysisEngine.php';
require_once __DIR__ . '/../engine/AIConnector.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Missing prompt field']);
    exit;
}

$prompt = $input['prompt'];
$source = $input['source'] ?? 'api';
$activityId = isset($input['activity_id']) ? intval($input['activity_id']) : null;
$destinationModel = $input['destination_model'] ?? null;

try {
    $engine = new AnalysisEngine();
    $result = $engine->analyze($prompt, $source);

    // AI forwarding: if prompt is safe or suspicious, send to destination model
    $aiResponse = null;
    if ($destinationModel && $result['verdict'] !== 'blocked') {
        $connector = new AIConnector();
        $aiResponse = $connector->sendPrompt(
            $result['was_sanitized'] ? $result['sanitized_text'] : $prompt,
            $destinationModel
        );
        $result['ai_response'] = $aiResponse;
    } elseif ($destinationModel && $result['verdict'] === 'blocked') {
        $result['ai_response'] = [
            'error' => false,
            'blocked' => true,
            'response' => '⛔ This prompt was BLOCKED by the security gateway. The destination AI model was not contacted to protect against potential threats.',
            'model' => 'none',
            'provider' => 'gateway',
            'response_time_ms' => 0,
        ];
    }

    // If within an activity, save the activity prompt record
    if ($activityId) {
        $pdo = Database::getInstance()->getConnection();

        // Get next sequence order
        $seqStmt = $pdo->prepare("SELECT COALESCE(MAX(sequence_order), 0) + 1 FROM activity_prompts WHERE activity_id = ?");
        $seqStmt->execute([$activityId]);
        $nextSeq = intval($seqStmt->fetchColumn());

        $stmt = $pdo->prepare("
            INSERT INTO activity_prompts 
            (activity_id, log_id, prompt_text, risk_score, verdict, ai_response, ai_model_used, ai_response_time_ms, sequence_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $activityId,
            $result['log_id'] ?? null,
            $prompt,
            $result['risk_score'],
            $result['verdict'],
            $aiResponse ? ($aiResponse['response'] ?? null) : null,
            $aiResponse ? ($aiResponse['model'] ?? $destinationModel) : null,
            $aiResponse ? ($aiResponse['response_time_ms'] ?? 0) : 0,
            $nextSeq,
        ]);

        $result['activity_prompt_id'] = $pdo->lastInsertId();
        $result['sequence_order'] = $nextSeq;

        // Update activity stats
        $pdo->prepare("
            UPDATE activities SET 
                total_prompts = (SELECT COUNT(*) FROM activity_prompts WHERE activity_id = ?),
                blocked_prompts = (SELECT COUNT(*) FROM activity_prompts WHERE activity_id = ? AND verdict = 'blocked'),
                avg_risk_score = (SELECT COALESCE(AVG(risk_score), 0) FROM activity_prompts WHERE activity_id = ?),
                updated_at = NOW()
            WHERE id = ?
        ")->execute([$activityId, $activityId, $activityId, $activityId]);
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Analysis failed: ' . $e->getMessage()]);
}
