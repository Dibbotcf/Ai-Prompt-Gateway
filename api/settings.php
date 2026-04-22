<?php
/**
 * API: Settings Management
 * GET  /api/settings.php          — Get all settings (masks API keys)
 * PUT  /api/settings.php          — Update settings
 * GET  /api/settings.php?providers=1 — Get AI provider status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance()->getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        if (isset($_GET['providers'])) {
            // Return AI provider availability status
            require_once __DIR__ . '/../engine/AIConnector.php';
            $connector = new AIConnector();
            echo json_encode([
                'success' => true,
                'providers' => $connector->getAvailableProviders(),
            ]);
            exit;
        }

        $stmt = $pdo->query("SELECT * FROM settings ORDER BY setting_key");
        $settings = $stmt->fetchAll();

        // Mask API keys for display
        foreach ($settings as &$s) {
            if (strpos($s['setting_key'], 'api_key') !== false && !empty($s['setting_value'])) {
                $val = $s['setting_value'];
                $s['setting_value_masked'] = substr($val, 0, 8) . '...' . substr($val, -4);
                $s['has_key'] = true;
            } else {
                $s['setting_value_masked'] = $s['setting_value'];
                $s['has_key'] = false;
            }
        }

        echo json_encode(['success' => true, 'settings' => $settings]);
    }

    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['settings'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No settings provided']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $updated = 0;

        foreach ($input['settings'] as $key => $value) {
            // Don't overwrite API key with masked value
            if (strpos($key, 'api_key') !== false && (strpos($value, '...') !== false || empty($value))) {
                if (empty($value)) {
                    $stmt->execute(['', $key]);
                    $updated++;
                }
                continue;
            }
            $stmt->execute([$value, $key]);
            $updated++;
        }

        echo json_encode(['success' => true, 'message' => "$updated settings updated"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
