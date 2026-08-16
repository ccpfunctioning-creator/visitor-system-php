<?php
// Standalone Local Database Configuration Management Framework
$dbFile = __DIR__ . '/database.sqlite';

try {
    // Open a persistent local database file stream container
    $db = new PDO("sqlite:" . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build standard data log tracking structures
    $db->exec("CREATE TABLE IF NOT EXISTS visitors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inmateName TEXT,
        inmateCid TEXT,
        block TEXT,
        visitorName TEXT NOT NULL,
        visitorCid TEXT NOT NULL,
        relationship TEXT,
        visitorType TEXT NOT NULL,
        cidPhoto TEXT NOT NULL,
        accompanyingData TEXT,
        status TEXT DEFAULT 'Pending',
        registeredAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        verifiedAt DATETIME
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS banned_inmates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inmateCid TEXT UNIQUE NOT NULL,
        reason TEXT
    )");

} catch (PDOException $e) {
    die("Local registry initialization crash: " . $e->getMessage());
}

// 🚀 SECURE GITHUB REMOTE STORAGE AUTOMATION AGENT BACKUP PIPELINE
function backupDatabaseToGitHub() {
    $username = 'ccpfunctioning-creator'; 
    $repo = 'visitor-system-php';
    $token = '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';
    $filePath = 'database.sqlite';
    $localFile = __DIR__ . '/' . $filePath;

    if (!file_exists($localFile)) return;

    $apiUrl = "https://github.com{$username}/{$repo}/contents/{$filePath}";
    $fileContent = base64_encode(file_get_contents($localFile));

    // Step A: Look up if a file history tag exists on GitHub to prevent conflict blocks
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token {$token}",
        "User-Agent: PHP-VRS-Database-Agent"
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $sha = null;
    if ($info['http_code'] == 200) {
        $result = json_decode($response, true);
        $sha = $result['sha'];
    }

    // Step B: Push the binary database file directly online into your repository
    $payload = [
        "message" => "chore: automatic visitor registry cluster database save [" . date('Y-m-d H:i:s') . "]",
        "content" => $fileContent,
        "branch" => "main"
    ];
    if ($sha) { $payload["sha"] = $sha; }

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token {$token}",
        "Content-Type: application/json",
        "User-Agent: PHP-VRS-Database-Agent"
    ]);
    curl_exec($ch);
    curl_close($ch);
}
?>
