<?php
// Initialize file-based SQLite database (Zero setup required!)
try {
    $db = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Visitors Registry Table
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
        status TEXT DEFAULT 'Pending',
        registeredAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        verifiedAt DATETIME
    )");

    // Create Banned Inmates Blacklist Table
    $db->exec("CREATE TABLE IF NOT EXISTS banned_inmates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inmateCid TEXT UNIQUE NOT NULL,
        reason TEXT
    )");

    // Seed dummy banned inmate for configuration checks if table is empty
    $check = $db->query("SELECT COUNT(*) FROM banned_inmates")->fetchColumn();
    if ($check == 0) {
        $stmt = $db->prepare("INSERT INTO banned_inmates (inmateCid, reason) VALUES (?, ?)");
        $stmt->execute(['111222333', 'Visitation privileges suspended due to disciplinary actions']);
    }

} catch (PDOException $e) {
    die("Database engine initialization failed: " . $e->getMessage());
}
?>
