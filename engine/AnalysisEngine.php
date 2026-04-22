<?php
/**
 * AI Prompt Security Gateway — Analysis Engine
 * Main orchestrator: accepts prompts, runs analysis, returns verdicts
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/RuleEngine.php';
require_once __DIR__ . '/Sanitizer.php';

class AnalysisEngine {
    private $ruleEngine;
    private $sanitizer;
    private $pdo;
    private $settings;

    // Default thresholds
    private $safeThreshold = 30;
    private $suspiciousThreshold = 65;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ruleEngine = new RuleEngine();
        $this->sanitizer = new Sanitizer();
        $this->loadSettings();
    }

    /**
     * Load settings from database
     */
    private function loadSettings() {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings");
            $rows = $stmt->fetchAll();
            $this->settings = [];
            foreach ($rows as $row) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }

            if (isset($this->settings['risk_threshold_safe'])) {
                $this->safeThreshold = intval($this->settings['risk_threshold_safe']);
            }
            if (isset($this->settings['risk_threshold_suspicious'])) {
                $this->suspiciousThreshold = intval($this->settings['risk_threshold_suspicious']);
            }
        } catch (Exception $e) {
            // Use defaults
        }
    }

    /**
     * Analyze a prompt and return structured results
     * 
     * @param string $prompt The raw prompt text
     * @param string $source Where the prompt came from (testbench, api, batch)
     * @param bool $shouldLog Whether to log this analysis
     * @return array Complete analysis results
     */
    public function analyze($prompt, $source = 'api', $shouldLog = true) {
        $startTime = microtime(true);

        // Validate input
        $maxLength = intval($this->settings['max_prompt_length'] ?? 10000);
        if (mb_strlen($prompt) > $maxLength) {
            return [
                'error' => true,
                'message' => "Prompt exceeds maximum length of $maxLength characters",
                'risk_score' => 100,
                'verdict' => 'blocked',
            ];
        }

        if (empty(trim($prompt))) {
            return [
                'error' => true,
                'message' => 'Prompt cannot be empty',
                'risk_score' => 0,
                'verdict' => 'safe',
            ];
        }

        // Run rule engine analysis
        $ruleResults = $this->ruleEngine->evaluate($prompt);

        // Run sanitization
        $sanitizationResults = $this->sanitizer->sanitize($prompt);

        // Determine verdict
        $riskScore = $ruleResults['risk_score'];
        $verdict = $this->determineVerdict($riskScore);

        $totalTimeMs = intval((microtime(true) - $startTime) * 1000);

        // Build response
        $response = [
            'error' => false,
            'prompt' => $prompt,
            'risk_score' => $riskScore,
            'verdict' => $verdict,
            'verdict_label' => $this->getVerdictLabel($verdict),
            'verdict_color' => $this->getVerdictColor($verdict),
            'matched_rules' => $ruleResults['matched_rules'],
            'matched_rules_count' => $ruleResults['matched_rules_count'],
            'categories_matched' => $ruleResults['categories_matched'],
            'categories_slugs' => $ruleResults['categories_slugs'],
            'sanitized_text' => $sanitizationResults['sanitized_text'],
            'was_sanitized' => $sanitizationResults['was_modified'],
            'sanitization_details' => $sanitizationResults['transformations'],
            'analysis_time_ms' => $totalTimeMs,
            'thresholds' => [
                'safe' => $this->safeThreshold,
                'suspicious' => $this->suspiciousThreshold,
            ],
        ];

        // Log to database
        if ($shouldLog && ($this->settings['enable_logging'] ?? 'true') === 'true') {
            $logId = $this->logAnalysis($response, $source);
            $response['log_id'] = $logId;
        }

        return $response;
    }

    /**
     * Determine the verdict based on risk score
     */
    private function determineVerdict($riskScore) {
        if ($riskScore <= $this->safeThreshold) {
            return 'safe';
        } elseif ($riskScore <= $this->suspiciousThreshold) {
            return 'suspicious';
        } else {
            return 'blocked';
        }
    }

    /**
     * Get human-readable verdict label
     */
    private function getVerdictLabel($verdict) {
        $labels = [
            'safe' => 'Safe — No threats detected',
            'suspicious' => 'Suspicious — Potential risks found',
            'blocked' => 'Blocked — High-risk content detected',
        ];
        return $labels[$verdict] ?? 'Unknown';
    }

    /**
     * Get verdict display color
     */
    private function getVerdictColor($verdict) {
        $colors = [
            'safe' => '#34c759',
            'suspicious' => '#ff9500',
            'blocked' => '#ff3b30',
        ];
        return $colors[$verdict] ?? '#8e8e93';
    }

    /**
     * Log analysis results to database
     */
    private function logAnalysis($results, $source) {
        $stmt = $this->pdo->prepare("
            INSERT INTO prompt_logs 
            (prompt_text, sanitized_text, risk_score, verdict, matched_rules_count, 
             categories_matched, analysis_time_ms, ip_address, user_agent, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $categoriesStr = !empty($results['categories_slugs']) 
            ? implode(',', $results['categories_slugs']) 
            : null;

        $stmt->execute([
            $results['prompt'],
            $results['was_sanitized'] ? $results['sanitized_text'] : null,
            $results['risk_score'],
            $results['verdict'],
            $results['matched_rules_count'],
            $categoriesStr,
            $results['analysis_time_ms'],
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $source,
        ]);

        $logId = $this->pdo->lastInsertId();

        // Log individual rule matches
        if (!empty($results['matched_rules'])) {
            $matchStmt = $this->pdo->prepare("
                INSERT INTO rule_matches (log_id, rule_id, matched_text, position_start, position_end)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($results['matched_rules'] as $match) {
                $matchStmt->execute([
                    $logId,
                    $match['rule_id'],
                    $match['matched_text'],
                    $match['position_start'],
                    $match['position_end'],
                ]);
            }
        }

        // Log sanitization details
        if (!empty($results['sanitization_details'])) {
            $sanitStmt = $this->pdo->prepare("
                INSERT INTO sanitization_log (log_id, original_fragment, sanitized_fragment, sanitization_type)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($results['sanitization_details'] as $detail) {
                $sanitStmt->execute([
                    $logId,
                    $detail['original'],
                    $detail['replacement'],
                    $detail['type'],
                ]);
            }
        }

        return $logId;
    }

    /**
     * Get dashboard statistics
     */
    public function getStats() {
        $stats = [];

        // Total scans
        $stats['total_scans'] = intval($this->pdo->query(
            "SELECT COUNT(*) FROM prompt_logs"
        )->fetchColumn());

        // Verdicts breakdown
        $stmt = $this->pdo->query("
            SELECT verdict, COUNT(*) as count 
            FROM prompt_logs 
            GROUP BY verdict
        ");
        $verdicts = ['safe' => 0, 'suspicious' => 0, 'blocked' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $verdicts[$row['verdict']] = intval($row['count']);
        }
        $stats['verdicts'] = $verdicts;

        // Block rate
        $stats['block_rate'] = $stats['total_scans'] > 0 
            ? round(($verdicts['blocked'] / $stats['total_scans']) * 100, 1) 
            : 0;

        // Average risk score
        $stats['avg_risk_score'] = round(floatval($this->pdo->query(
            "SELECT COALESCE(AVG(risk_score), 0) FROM prompt_logs"
        )->fetchColumn()), 1);

        // Average analysis time
        $stats['avg_analysis_time'] = round(floatval($this->pdo->query(
            "SELECT COALESCE(AVG(analysis_time_ms), 0) FROM prompt_logs"
        )->fetchColumn()), 1);

        // Category breakdown
        $stmt = $this->pdo->query("
            SELECT ac.name, ac.slug, ac.color, ac.icon, COUNT(rm.id) as hit_count
            FROM attack_categories ac
            LEFT JOIN rules r ON r.category_id = ac.id
            LEFT JOIN rule_matches rm ON rm.rule_id = r.id
            GROUP BY ac.id
            ORDER BY hit_count DESC
        ");
        $stats['category_breakdown'] = $stmt->fetchAll();

        // Time series (last 30 days)
        $stmt = $this->pdo->query("
            SELECT DATE(created_at) as date,
                   COUNT(*) as total,
                   SUM(CASE WHEN verdict = 'safe' THEN 1 ELSE 0 END) as safe_count,
                   SUM(CASE WHEN verdict = 'suspicious' THEN 1 ELSE 0 END) as suspicious_count,
                   SUM(CASE WHEN verdict = 'blocked' THEN 1 ELSE 0 END) as blocked_count
            FROM prompt_logs
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stats['time_series'] = $stmt->fetchAll();

        // Top triggered rules
        $stmt = $this->pdo->query("
            SELECT r.name, r.severity, r.match_count, ac.name as category_name, ac.color
            FROM rules r
            JOIN attack_categories ac ON r.category_id = ac.id
            WHERE r.match_count > 0
            ORDER BY r.match_count DESC
            LIMIT 10
        ");
        $stats['top_rules'] = $stmt->fetchAll();

        // Recent activity
        $stmt = $this->pdo->query("
            SELECT id, LEFT(prompt_text, 100) as prompt_preview, 
                   risk_score, verdict, categories_matched, 
                   analysis_time_ms, source, created_at
            FROM prompt_logs
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stats['recent_activity'] = $stmt->fetchAll();

        // Active rules count
        $stats['active_rules'] = intval($this->pdo->query(
            "SELECT COUNT(*) FROM rules WHERE is_active = 1"
        )->fetchColumn());

        // Total rules count
        $stats['total_rules'] = intval($this->pdo->query(
            "SELECT COUNT(*) FROM rules"
        )->fetchColumn());

        return $stats;
    }

    /**
     * Get prompt logs with filtering
     */
    public function getLogs($filters = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['verdict'])) {
            $where[] = "pl.verdict = ?";
            $params[] = $filters['verdict'];
        }

        if (!empty($filters['category'])) {
            $where[] = "pl.categories_matched LIKE ?";
            $params[] = "%{$filters['category']}%";
        }

        if (!empty($filters['search'])) {
            $where[] = "pl.prompt_text LIKE ?";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['date_from'])) {
            $where[] = "pl.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "pl.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['risk_min'])) {
            $where[] = "pl.risk_score >= ?";
            $params[] = intval($filters['risk_min']);
        }

        $whereStr = implode(' AND ', $where);
        $limit = intval($filters['limit'] ?? 100);
        $offset = intval($filters['offset'] ?? 0);

        $stmt = $this->pdo->prepare("
            SELECT pl.*
            FROM prompt_logs pl
            WHERE $whereStr
            ORDER BY pl.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Get total count for pagination
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM prompt_logs pl WHERE $whereStr
        ");
        $countStmt->execute($params);
        $total = intval($countStmt->fetchColumn());

        return [
            'logs' => $logs,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Get a single log entry with full details
     */
    public function getLogDetail($logId) {
        $stmt = $this->pdo->prepare("SELECT * FROM prompt_logs WHERE id = ?");
        $stmt->execute([$logId]);
        $log = $stmt->fetch();

        if (!$log) return null;

        // Get matched rules
        $stmt = $this->pdo->prepare("
            SELECT rm.*, r.name as rule_name, r.severity, r.pattern_type,
                   ac.name as category_name, ac.color as category_color
            FROM rule_matches rm
            JOIN rules r ON rm.rule_id = r.id
            JOIN attack_categories ac ON r.category_id = ac.id
            WHERE rm.log_id = ?
        ");
        $stmt->execute([$logId]);
        $log['rule_matches'] = $stmt->fetchAll();

        // Get sanitization details  
        $stmt = $this->pdo->prepare("SELECT * FROM sanitization_log WHERE log_id = ?");
        $stmt->execute([$logId]);
        $log['sanitization_details'] = $stmt->fetchAll();

        return $log;
    }
}
