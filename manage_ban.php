<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Restrict access strictly to the Admin Account role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$actionMessage = null;

// Handle Adding an Inmate to the Restricted List
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ban'])) {
    $inmateCid = trim($_POST['inmateCid'] ?? '');
    $reason = trim($_POST['reason'] ?? 'Administrative Restriction Notice');

    if (!empty($inmateCid)) {
        try {
            $stmt = $db->prepare("INSERT INTO banned_inmates (inmateCid, reason) VALUES (?, ?)");
            $stmt->execute([$inmateCid, $reason]);
            backupDatabaseToGitHub(); // Sync database save state to GitHub
            $actionMessage = "✅ Inmate CID successfully added to the system restriction log.";
        } catch (PDOException $e) {
            $actionMessage = "⚠️ Error: This inmate CID is already restricted.";
        }
    }
}

// Handle Removing an Inmate from the Restricted List
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_ban_id'])) {
    $banId = $_POST['remove_ban_id'];
    $stmt = $db->prepare("DELETE FROM banned_inmates WHERE id = ?");
    $stmt->execute([$banId]);
    backupDatabaseToGitHub(); // Sync database save state to GitHub
    $actionMessage = "✅ Restriction successfully lifted. Inmate cleared for visitation.";
}

// Fetch all currently restricted inmates
$stmt = $db->query("SELECT * FROM banned_inmates ORDER BY id DESC");
$bannedList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<div class="container py-3 py-md-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Ecosystem Restriction Registry</h3>
            <p class="text-secondary small m-0">Suspend or restore visitation privileges for specific inmate accounts.</p>
        </div>
        <a href="admin.php" class="btn btn-outline-secondary px-3 py-2 rounded-3 small fw-semibold">
            ⬅️ Back to Admin Control
        </a>
    </div>

    <?php if ($actionMessage): ?>
        <div class="alert alert-info py-2.5 px-3 rounded-3 small fw-semibold shadow-sm mb-4"><?php echo $actionMessage; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Side: Add Restriction Parameter Form -->
        <div class="col-12 col-md-4">
            <div class="beautiful-card p-4 bg-white shadow-sm" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                <h5 class="fw-bold text-dark mb-3" style="font-size: 1rem;">Log New Restriction</h5>
                <form method="POST" action="manage_ban.php">
                    <input type="hidden" name="add_ban" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Inmate National CID</label>
                        <input type="text" name="inmateCid" class="form-control" style="border-radius: 10px; padding: 0.6rem;" placeholder="Enter Inmate CID Number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Reason for Suspension</label>
                        <input type="text" name="reason" class="form-control" style="border-radius: 10px; padding: 0.6rem;" placeholder="e.g., Disciplinary Action" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 py-2.5 fw-bold" style="border-radius: 10px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                        🔒 Suspend Privileges
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Side: Active Restrictions Log Directory -->
        <div class="col-12 col-md-8">
            <div class="table-responsive shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark" style="background-color: #0f172a !important;">
                        <tr class="small text-uppercase">
                            <th class="p-3">Restricted Inmate CID</th>
                            <th class="p-3">Suspension Logs Reason</th>
                            <th class="p-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (count($bannedList) === 0): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted bg-white border-0 fw-semibold">
                                    🟢 No active inmate visitation restrictions logged in the database.
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($bannedList as $row): ?>
                            <tr>
                                <td class="p-3 font-monospace fw-bold text-dark"><?php echo htmlspecialchars($row['inmateCid']); ?></td>
                                <td class="p-3 text-secondary"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="p-3 text-center">
                                    <form method="POST" action="manage_ban.php" onsubmit="return confirm('Are you sure you want to lift this restriction?');">
                                        <input type="hidden" name="remove_ban_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success px-3 rounded-pill fw-semibold">
                                            🔓 Lift Restriction
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
