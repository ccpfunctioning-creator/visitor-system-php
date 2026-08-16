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

    // Fallback: If database file doesn't exist yet, force touch it
    if (!file_exists($localFile)) {
        file_put_contents($localFile, '');
    }

    $apiUrl = "https://github.com{$username}/{$repo}/contents/{$filePath}";
    $fileContent = base64_encode(file_get_contents($localFile));

    // Step A: Look up file history tag on GitHub with forced stream headers
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Authorization: token {$token}\r\n" .
                        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($apiUrl, false, $context);

    $sha = null;
    if ($response !== false) {
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

    $putOpts = [
        "http" => [
            "method" => "PUT",
            "header" => "Authorization: token {$token}\r\n" .
                        "Content-Type: application/json\r\n" .
                        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
            "content" => json_encode($payload)
        ]
    ];
    $putContext = stream_context_create($putOpts);
    @file_get_contents($apiUrl, false, $putContext);
}
?>
