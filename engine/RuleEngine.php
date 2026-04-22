<?php
/**
 * AI Prompt Security Gateway — Rule Engine
 * Evaluates prompts against active detection rules
 */

require_once __DIR__ . '/../config/database.php';

class RuleEngine {
    private $pdo;
    private $rules = [];
    private $categories = [];

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->loadRules();
        $this->loadCategories();
    }

    /**
     * Load all active rules from the database
     */
    private function loadRules() {
        $stmt = $this->pdo->query("
            SELECT r.*, ac.slug as category_slug, ac.name as category_name, 
                   ac.severity_weight as category_weight
            FROM rules r
            JOIN attack_categories ac ON r.category_id = ac.id
            WHERE r.is_active = 1
            ORDER BY r.severity_score DESC
        ");
        $this->rules = $stmt->fetchAll();
    }

    /**
     * Load attack categories
     */
    private function loadCategories() {
        $stmt = $this->pdo->query("SELECT * FROM attack_categories ORDER BY id");
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $this->categories[$row['id']] = $row;
        }
    }

    /**
     * Evaluate a prompt against all active rules
     * 
     * @param string $prompt The prompt text to analyze
     * @return array Analysis results with matched rules and risk score
     */
    public function evaluate($prompt) {
        $startTime = microtime(true);
        $matches = [];
        $totalScore = 0;
        $categoriesHit = [];
        $promptLower = mb_strtolower($prompt);

        foreach ($this->rules as $rule) {
            $matched = false;
            $matchedText = '';
            $posStart = 0;
            $posEnd = 0;

            switch ($rule['pattern_type']) {
                case 'regex':
                    if (@preg_match($rule['pattern'], $prompt, $m, PREG_OFFSET_CAPTURE)) {
                        $matched = true;
                        $matchedText = $m[0][0];
                        $posStart = $m[0][1];
                        $posEnd = $posStart + strlen($matchedText);
                    }
                    break;

                case 'keyword':
                    $keywords = array_map('trim', explode(',', mb_strtolower($rule['pattern'])));
                    foreach ($keywords as $keyword) {
                        $pos = mb_strpos($promptLower, $keyword);
                        if ($pos !== false) {
                            $matched = true;
                            $matchedText = mb_substr($prompt, $pos, mb_strlen($keyword));
                            $posStart = $pos;
                            $posEnd = $pos + mb_strlen($keyword);
                            break;
                        }
                    }
                    break;

                case 'phrase':
                    if (mb_strpos($promptLower, mb_strtolower($rule['pattern'])) !== false) {
                        $matched = true;
                        $matchedText = $rule['pattern'];
                        $posStart = mb_strpos($promptLower, mb_strtolower($rule['pattern']));
                        $posEnd = $posStart + mb_strlen($rule['pattern']);
                    }
                    break;
            }

            if ($matched) {
                $categoryWeight = floatval($rule['category_weight']);
                $weightedScore = intval($rule['severity_score'] * $categoryWeight);

                $matches[] = [
                    'rule_id' => $rule['id'],
                    'rule_name' => $rule['name'],
                    'description' => $rule['description'],
                    'category_id' => $rule['category_id'],
                    'category_slug' => $rule['category_slug'],
                    'category_name' => $rule['category_name'],
                    'severity' => $rule['severity'],
                    'severity_score' => $rule['severity_score'],
                    'weighted_score' => $weightedScore,
                    'pattern_type' => $rule['pattern_type'],
                    'matched_text' => $matchedText,
                    'position_start' => $posStart,
                    'position_end' => $posEnd,
                ];

                $totalScore += $weightedScore;
                $categoriesHit[$rule['category_slug']] = $rule['category_name'];

                // Increment match count for this rule
                $this->incrementMatchCount($rule['id']);
            }
        }

        // Cap the score at 100
        $riskScore = min(100, $totalScore);

        $analysisTimeMs = intval((microtime(true) - $startTime) * 1000);

        return [
            'risk_score' => $riskScore,
            'matched_rules' => $matches,
            'matched_rules_count' => count($matches),
            'categories_matched' => array_values($categoriesHit),
            'categories_slugs' => array_keys($categoriesHit),
            'analysis_time_ms' => $analysisTimeMs,
        ];
    }

    /**
     * Increment the match count for a rule
     */
    private function incrementMatchCount($ruleId) {
        $stmt = $this->pdo->prepare("UPDATE rules SET match_count = match_count + 1 WHERE id = ?");
        $stmt->execute([$ruleId]);
    }

    /**
     * Get all rules (for management)
     */
    public function getAllRules($includeInactive = true) {
        $where = $includeInactive ? '' : 'WHERE r.is_active = 1';
        $stmt = $this->pdo->query("
            SELECT r.*, ac.name as category_name, ac.slug as category_slug, ac.color as category_color
            FROM rules r
            JOIN attack_categories ac ON r.category_id = ac.id
            $where
            ORDER BY r.category_id, r.severity_score DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get all categories
     */
    public function getCategories() {
        return $this->categories;
    }

    /**
     * Add a new rule
     */
    public function addRule($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO rules (name, description, category_id, pattern, pattern_type, severity, severity_score, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['category_id'],
            $data['pattern'],
            $data['pattern_type'] ?? 'regex',
            $data['severity'] ?? 'medium',
            $data['severity_score'] ?? 50,
            $data['is_active'] ?? 1,
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update an existing rule
     */
    public function updateRule($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE rules SET name=?, description=?, category_id=?, pattern=?, 
            pattern_type=?, severity=?, severity_score=?, is_active=?
            WHERE id=?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['category_id'],
            $data['pattern'],
            $data['pattern_type'] ?? 'regex',
            $data['severity'] ?? 'medium',
            $data['severity_score'] ?? 50,
            $data['is_active'] ?? 1,
            $id,
        ]);
    }

    /**
     * Toggle rule active status
     */
    public function toggleRule($id) {
        $stmt = $this->pdo->prepare("UPDATE rules SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a rule
     */
    public function deleteRule($id) {
        $stmt = $this->pdo->prepare("DELETE FROM rules WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get a single rule by ID
     */
    public function getRule($id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, ac.name as category_name 
            FROM rules r 
            JOIN attack_categories ac ON r.category_id = ac.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
