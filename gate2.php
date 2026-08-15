<?php
session_start();
// Both Gate 2 and Admin roles are cleared to manage security verification desk tasks
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'gate2' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Process instant status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_id'])) {
    $stmt = $db->prepare("UPDATE visitors SET status = 'Verified', verifiedAt = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$_POST['verify_id']]);
    header("Location: gate2.php");
    exit;
}

$searchId = isset($_GET['searchId']) ? $_GET['searchId'] : null;
if ($searchId) {
    $stmt = $db->prepare("SELECT * FROM visitors WHERE id = ?");
    $stmt->execute([$searchId]);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->query("SELECT * FROM visitors WHERE status = 'Pending' ORDER BY registeredAt ASC");
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include 'header.php'; ?>
<div class="w-100">
    <h3 class="mb-4">Gate 2: Document Verification Registry Desk</h3>
    <div class="row">
        <?php if (count($queue) === 0): ?>
            <div class="alert alert-info">No pending tracking applications awaiting verification checks right now.</div>
        <?php endif; ?>
        <?php foreach ($queue as $item): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow border-0 bg-white p-3">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Visitor: <?php echo htmlspecialchars($item['visitorName']); ?> <span class="badge bg-warning text-dark"><?php echo $item['visitorType']; ?></span></h5>
                            <p class="mb-1"><strong>Visitor CID:</strong> <?php echo htmlspecialchars($item['visitorCid']); ?></p>
                            <?php if ($item['visitorType'] !== 'Others'): ?>
                                <hr><h6>Visiting Inmate:</h6>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($item['inmateName']); ?> (<?php echo $item['block']; ?>)</p>
                                <p class="mb-1"><strong>Inmate CID:</strong> <?php echo htmlspecialchars($item['inmateCid']); ?></p>
                                <p class="mb-1"><strong>Relationship:</strong> <?php echo htmlspecialchars($item['relationship']); ?></p>
                            <?php else: ?>
                                <p class="text-muted small">Classification Profile: External Base Event</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-center d-flex flex-column justify-content-between">
                            <img src="<?php echo $item['cidPhoto']; ?>" class="img-thumbnail img-fluid mb-2 mx-auto" style="max-height: 120px;" alt="Snapshot">
                            <form action="gate2.php" method="POST">
                                <input type="hidden" name="verify_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-success w-100 btn-sm fw-bold">Confirm & Verify</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
