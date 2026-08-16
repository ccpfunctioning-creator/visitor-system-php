<?php
// Initialize session at the absolute top of the page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Restrict access to Gate 2 duty staff and Administrators only
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'gate2' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$actionMessage = null;

// Process Gate 2 Status Decisions (Verify or Reject Actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_id'])) {
    $recordId = $_POST['action_id'];
    $decision = $_POST['decision'] ?? '';

    if ($decision === 'Verify') {
        $stmt = $db->prepare("UPDATE visitors SET status = 'Verified', verifiedAt = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$recordId]);
        $actionMessage = "✅ Record successfully cleared and marked as Verified.";
    } elseif ($decision === 'Reject') {
        $stmt = $db->prepare("UPDATE visitors SET status = 'Rejected', verifiedAt = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$recordId]);
        $actionMessage = "❌ Record successfully blocked and marked as Rejected.";
    }
}

// Look up a specific record if a barcode/token pass reference ID parameter is present
$searchId = isset($_GET['searchId']) ? $_GET['searchId'] : null;
if ($searchId) {
    $stmt = $db->prepare("SELECT * FROM visitors WHERE id = ?");
    $stmt->execute([$searchId]);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Default: Pull all active, unchecked applications awaiting desk validation
    $stmt = $db->query("SELECT * FROM visitors WHERE status = 'Pending' ORDER BY registeredAt ASC");
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include 'header.php'; ?>

<style>
    .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .duty-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    .duty-photo-frame {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        max-width: 100%;
        width: 240px;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container py-2 py-md-4 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Gate 2: Credentials Audit &amp; Verification Desk</h3>
            <p class="text-secondary small m-0">Crosscheck physical identity papers against Gate 1 digital records.</p>
        </div>
        <span class="badge bg-dark px-3 py-2 rounded-pill font-monospace small">Active Duty Queue</span>
    </div>

    <?php if ($actionMessage): ?>
        <div class="alert alert-info py-2.5 px-3 rounded-3 small fw-semibold shadow-sm mb-4"><?php echo $actionMessage; ?></div>
    <?php endif; ?>

    <?php if (count($queue) === 0): ?>
        <div class="alert alert-light text-center py-5 rounded-4 shadow-sm border border-dashed">
            <h5 class="text-muted fw-bold m-0">🎉 All Clear! No Pending Entries</h5>
            <p class="text-secondary small m-0 mt-1">New registrations from Gate 1 will appear here in real-time.</p>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($queue as $item): ?>
            <div class="col-12 mb-4">
                <div class="duty-card shadow-sm">
                    <div class="row g-0">
                        
                        <!-- Left Panel: Visitor & Accompanying Details -->
                        <div class="col-12 col-lg-8 p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <h4 class="fw-bold text-primary mb-1" style="letter-spacing: -0.5px;"><?php echo htmlspecialchars($item['visitorName']); ?></h4>
                                    <div class="d-flex flex-wrap gap-1.5 align-items-center mt-1">
                                        <span class="badge bg-light text-secondary border font-monospace small">Primary CID: <?php echo htmlspecialchars($item['visitorCid']); ?></span>
                                        <span class="badge bg-primary text-white small" style="background-color: #6366f1 !important;"><?php echo $item['visitorType']; ?> Mode</span>
                                    </div>
                                </div>
                                <span class="text-muted font-monospace small bg-light px-2 py-1 rounded border"><?php echo $item['registeredAt']; ?></span>
                            </div>

                            <!-- Inmate Allocation Summary Card -->
                            <?php if ($item['visitorType'] !== 'Others'): ?>
                                <div class="p-3 bg-light rounded-3 my-3 border border-light shadow-sm">
                                    <div class="text-uppercase tracking-wider small fw-bold text-secondary mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Target Inmate Destination</div>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['inmateName']); ?> <span class="text-muted font-normal small">(CID: <?php echo htmlspecialchars($item['inmateCid']); ?>)</span></div>
                                    <div class="mt-1">
                                        <span class="badge bg-dark small"><?php echo $item['block']; ?></span>
                                        <span class="text-secondary small ms-1">Relationship: <strong><?php echo htmlspecialchars($item['relationship']); ?></strong></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-3 bg-light text-muted small rounded-3 my-3 border border-dashed">
                                    ⚙️ Classification Profile: General External Visit (No Inmate Association Field)
                                </div>
                            <?php endif; ?>

                            <!-- Accompanying Group Members Sub-Grid -->
                            <h6 class="fw-bold mt-3 text-secondary d-flex align-items-center gap-1 small text-uppercase" style="letter-spacing: 0.5px;">👥 Group Members Accompanied</h6>
                            <?php 
                            $accompaniedList = !empty($item['accompanyingData']) ? json_decode($item['accompanyingData'], true) : [];
                            if (!empty($accompaniedList)): 
                            ?>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-hover table-bordered bg-white rounded-3 overflow-hidden align-middle mb-0">
                                        <thead class="table-light text-secondary small">
                                            <tr><th>Full Name</th><th>National CID No.</th><th>Relationship</th></tr>
                                        </thead>
                                        <tbody class="small">
                                            <?php foreach ($accompaniedList as $acc): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($acc['name']); ?></strong></td>
                                                    <td class="font-monospace text-secondary"><?php echo htmlspecialchars($acc['cid']); ?></td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($acc['relation']); ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small ps-1 m-0 mt-1">Individual Entry (No accompanying roster logs attached).</p>
                            <?php endif; ?>
                        </div>

                        <!-- Right Panel: Photo Verification & Decision Controls -->
                        <div class="col-12 col-lg-4 p-3 p-md-4 bg-light border-start d-flex flex-column justify-content-between align-items-center text-center">
                            <div class="w-100 d-flex flex-column align-items-center">
                                <div class="text-uppercase tracking-wider small fw-bold text-secondary mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Gate 1 Photo Document Audit</div>
                                
                                <?php if (!empty($item['cidPhoto'])): ?>
                                    <div class="duty-photo-frame mb-2">
                                        <img src="<?php echo htmlspecialchars($item['cidPhoto']); ?>" class="img-fluid rounded-2" style="max-height: 180px; width: 100%; object-fit: contain; display: block;" alt="CID Document File">
                                    </div>
                                    <a href="<?php echo htmlspecialchars($item['cidPhoto']); ?>" target="_blank" class="btn btn-link text-decoration-none text-primary small p-0 mb-3" style="font-size: 0.8rem;">🔍 Zoom Document File</a>
                                <?php else: ?>
                                    <div class="alert alert-secondary small py-4 rounded-3 mb-3 w-100">No physical ID paper snapshot file found.</div>
                                <?php endif; ?>
                            </div>

                            <form action="gate2.php" method="POST" class="w-100">
                                <input type="hidden" name="action_id" value="<?php echo $item['id']; ?>">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="submit" name="decision" value="Reject" class="btn btn-outline-danger w-100 py-2 fw-bold rounded-3 small">
                                            ✕ Reject Entry
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="submit" name="decision" value="Verify" class="btn btn-success w-100 py-2 fw-bold text-white shadow-sm rounded-3 small" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                            ✓ Verify Entry
                                        </button>
                                    </div>
                                </div>
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
