<?php
// 🚀 UNIVERSAL UNLOCKED DATA STORAGE PATH
$dbFile = '/tmp/database.sqlite';

try {
    // Open a persistent database file stream container safely inside /tmp
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

// 🚀 BULLETPROOF LOCAL TEXT FILE BACKUP GENERATOR (Requires zero internet keys)
function backupDatabaseToGitHub() {
    $dbFile = '/tmp/database.sqlite';
    $backupTextFile = __DIR__ . '/visitors_backup.txt'; // Permanent visible backup file

    if (!file_exists($dbFile)) return;

    try {
        $localDb = new PDO("sqlite:" . $dbFile);
        $stmt = $localDb->query("SELECT * FROM visitors ORDER BY registeredAt DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $logData = "==================================================\n";
        $logData .= "   VISITOR LOG DATABASE BACKUP - " . date('Y-m-d H:i:s') . "\n";
        $logData .= "==================================================\n\n";

        foreach ($rows as $row) {
            $logData .= "ID: #" . $row['id'] . "\n";
            $logData .= "Timestamp: " . $row['registeredAt'] . "\n";
            $logData .= "Visitor Name: " . $row['visitorName'] . " (CID: " . $row['visitorCid'] . ")\n";
            $logData .= "Classification: " . $row['visitorType'] . "\n";
            if (!empty($row['inmateName'])) {
                $logData .= "Target Inmate: " . $row['inmateName'] . " (CID: " . $row['inmateCid'] . ") [" . $row['block'] . "]\n";
            }
            $logData .= "Status: [" . $row['status'] . "]\n";
            $logData .= "--------------------------------------------------\n";
        }

        // Lock data into a standard flat text file that Render cannot block
        file_put_contents($backupTextFile, $logData, LOCK_EX);
    } catch (Exception $e) {
        // Fallback catch block to prevent form submission page breaks
    }
}
?>
