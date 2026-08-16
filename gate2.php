<?php
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

// Process Verify or Reject Status Decisions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_id'])) {
    $recordId = $_POST['action_id'];
    $decision = $_POST['decision'] ?? '';
    $statusText = ($decision === 'Verify') ? 'Verified' : 'Rejected';
    
    $payload = [
        'status' => $statusText,
        'verified_at' => date('Y-m-d H:i:s')
    ];
    
    // Direct cloud record status updates
    querySupabaseCloud("visitors?id=eq." . $recordId, "PATCH", $payload);
    $actionMessage = "✅ Entry record successfully processed and marked as: " . $statusText;
}

// Check if a specific visitor tracking token pass reference code lookup is active
$searchId = $_GET['searchId'] ?? null;
if ($searchId) {
    $queue = querySupabaseCloud("visitors?id=eq." . urlencode($searchId), "GET");
} else {
    // Default view: Query all active incoming applications waiting at the checkpoint desk
    $queue = querySupabaseCloud("visitors?status=eq.Pending&order=registered_at.asc", "GET");
}
?>

<?php include 'header.php'; ?>

<style>
    .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .duty-card { background: #ffffff; border-radius: 20px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.04); overflow: hidden; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; }
    .duty-photo-frame { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 0.5rem; width: 240px; margin: 0 auto; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container py-2 py-md-4 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Gate 2: Credentials Audit &amp; Verification Desk</h3>
            <p class="text-secondary small m-0">Crosscheck physical identity papers against permanent cloud entries.</p>
        </div>
        <span class="badge bg-dark px-3 py-2 rounded-pill font-monospace small">Active Duty Queue</span>
    </div>

    <?php if ($actionMessage): ?>
        <div class="alert alert-info py-2 px-3 rounded-3 small fw-semibold shadow-sm mb-4"><?php echo $actionMessage; ?></div>
    <?php endif; ?>

    <?php if (empty($queue) || !is_array($queue)): ?>
        <div class="alert alert-light text-center py-5 rounded-4 shadow-sm border border-dashed">
            <h5 class="text-muted fw-bold m-0">🎉 All Clear! No Pending Entries</h5>
            <p class="text-secondary small m-0 mt-1">New registrations filed from Gate 1 desks will pop up right here live.</p>
        </div>
    <?php else: ?>
        <?php foreach ($queue as $item): ?>
            <div class="duty-card shadow-sm animate-fade-in">
                <div class="row g-0">
                    
                    <!-- Profile Logs Column Block -->
                    <div class="col-12 col-lg-8 p-3 p-md-4">
                        <h4 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($item['visitor_name']); ?></h4>
                        <div class="d-flex flex-wrap gap-1.5 align-items-center mt-1 mb-3">
                            <span class="badge bg-light text-secondary border font-monospace small">Primary CID: <?php echo htmlspecialchars($item['visitor_cid']); ?></span>
                            <span class="badge bg-primary text-white small" style="background-color: #6366f1 !important;"><?php echo htmlspecialchars($item['visitor_type']); ?> Visit</span>
                        </div>
                        
                        <?php if (!empty($item['inmate_name'])): ?>
                            <div class="p-3 bg-light rounded-3 my-3 border border-light shadow-sm">
                                <div class="text-uppercase tracking-wider small fw-bold text-secondary mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Target Inmate Destination</div>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['inmate_name']); ?> <span class="text-muted font-normal small">(CID: <?php echo htmlspecialchars($item['inmate_cid']); ?>)</span></div>
                                <div class="mt-1">
                                    <span class="badge bg-dark small"><?php echo htmlspecialchars($item['block']); ?></span>
                                    <span class="text-secondary small ms-1">Relationship: <strong><?php echo htmlspecialchars($item['relationship']); ?></strong></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Accompanying Roster Grid Rendering -->
                        <h6 class="fw-bold mt-3 text-secondary small text-uppercase" style="letter-spacing: 0.5px;">👥 Group Members Accompanied</h6>
                        <?php 
                        $accompaniedList = !empty($item['accompanying_data']) ? (is_string($item['accompanying_data']) ? json_decode($item['accompanying_data'], true) : $item['accompanying_data']) : [];
                        if (!empty($accompaniedList) && is_array($accompaniedList)): 
                        ?>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-hover table-bordered bg-white rounded-3 overflow-hidden mb-0">
                                    <thead class="table-light text-secondary small">
                                        <tr><th>Full Name</th><th>National CID No.</th><th>Relationship</th></tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php foreach ($accompaniedList as $acc): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($acc['name'] ?? ''); ?></strong></td>
                                                <td class="font-monospace text-secondary"><?php echo htmlspecialchars($acc['cid'] ?? ''); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($acc['relation'] ?? ''); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small m-0 mt-1">Individual Entry (No accompanying roster logs attached).</p>
                        <?php endif; ?>
                    </div>

                    <!-- Photo Validation Controls Column Block -->
                    <div class="col-12 col-lg-4 p-3 p-md-4 bg-light d-flex flex-column justify-content-between align-items-center text-center border-start">
                        <div class="w-100 d-flex flex-column align-items-center">
                            <div class="text-uppercase tracking-wider small fw-bold text-secondary mb-3" style="font-size: 0.72rem; letter-spacing: 0.5px;">Gate 1 Photo Document Audit</div>
                            <?php if (!empty($item['cid_photo'])): ?>
                                <div class="duty-photo-frame mb-3">
                                    <img src="<?php echo $item['cid_photo']; ?>" class="img-fluid rounded-2" style="max-height: 180px; width: 100%; object-fit: contain; display: block;" alt="CID File">
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary small py-4 rounded-3 mb-3 w-100">No verification photo snapshot file uploaded.</div>
                            <?php endif; ?>
                        </div>

                        <form action="gate2.php" method="POST" class="w-100 mt-2">
                            <input type="hidden" name="action_id" value="<?php echo $item['id']; ?>">
                            <div class="row g-2">
                                <div class="col-6">
                                    <button type="submit" name="decision" value="Reject" class="btn btn-outline-danger w-100 py-2 fw-bold rounded-3 small">✕ Reject Entry</button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="decision" value="Verify" class="btn btn-success w-100 py-2 fw-bold text-white shadow-sm rounded-3 small" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">✓ Verify Entry</button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
