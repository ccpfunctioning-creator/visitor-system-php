<?php
try {
    $db = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        accompanyingData TEXT, /* 💡 New Field Column to hold the accompanying array text */
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
    die("Database engine initialization failed: " . $e->getMessage());
}
?>
