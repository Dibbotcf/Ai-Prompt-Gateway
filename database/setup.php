<?php
/**
 * AI Prompt Security Gateway — Database Setup
 * Run this file once to create the database and seed data.
 * Access via: http://localhost:7882/prompt-gateway/database/setup.php
 */

header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$port = 3306;

echo "<h2>AI Prompt Security Gateway — Database Setup</h2>";
echo "<pre>";

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✓ Connected to MySQL server\n";

    // Read and execute schema
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql not found at: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($s) { return !empty($s) && $s !== ''; }
    );

    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
    }
    echo "✓ Database schema created successfully\n";

    // Read and execute seed data
    $seedFile = __DIR__ . '/seed_data.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("seed_data.sql not found at: $seedFile");
    }

    $seed = file_get_contents($seedFile);
    $statements = array_filter(
        array_map('trim', explode(';', $seed)),
        function($s) { return !empty($s) && $s !== ''; }
    );

    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
    }
    echo "✓ Seed data inserted successfully\n";

    // Verify
    $pdo->exec("USE prompt_gateway");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\n✓ Tables created: " . implode(', ', $tables) . "\n";

    $ruleCount = $pdo->query("SELECT COUNT(*) FROM rules")->fetchColumn();
    $catCount = $pdo->query("SELECT COUNT(*) FROM attack_categories")->fetchColumn();
    $logCount = $pdo->query("SELECT COUNT(*) FROM prompt_logs")->fetchColumn();
    echo "✓ Categories: $catCount | Rules: $ruleCount | Sample Logs: $logCount\n";

    echo "\n<b>✅ Setup complete!</b>\n";
    echo "<a href='../'>→ Go to Dashboard</a>\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
