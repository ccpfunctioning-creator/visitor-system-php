<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Enforcement Layer: Restrict checkpoint to logged-in operators
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['gate2', 'admin'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$searchQuery = $_GET['search'] ?? '';
$searchResult = null;
$searchAttempted = false;
$updateMessage = null;

// Handle Verification Pass Status Check-In Operations (Check-In / Out toggles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_id'])) {
    $actionId = $_POST['action_id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = ($currentStatus === 'Pending') ? 'Checked-In' : 'Checked-Out';
    
    $updatePayload = [
        'status' => $newStatus,
        'verified_at' => date('Y-m-d H:i:s')
    ];

    querySupabaseCloud('visitors', 'UPDATE', $updatePayload, ['id' => 'eq.' . $actionId]);
    $updateMessage = "✅ Log Successfully Updated: Clear Pass Status changed to <strong>{$newStatus}</strong>.";
    
    // Re-fetch target record
    $records = querySupabaseCloud('visitors', 'SELECT', [], ['id' => 'eq.' . $actionId]);
    if (!empty($records) && is_array($records)) {
        // Deep loop extraction fallback to break double nested array shells
        while (isset($records[0]) && is_array($records[0])) {
            $records = $records[0];
        }
        $searchResult = $records;
    }
    $searchAttempted = true;
}

// Handle Checkpoint Security Queue Index Card Queries via Visitor National CID number
if (!empty($searchQuery) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $records = querySupabaseCloud('visitors', 'SELECT', [], ['visitor_cid' => 'eq.' . trim($searchQuery)]);
    
    if (!empty($records) && is_array($records)) {
        // 🎯 DYNAMIC ROW SHELL UNPACKER: Strips nested arrays until reaching the raw record object
        while (isset($records[0]) && is_array($records[0])) {
            $records = $records[0];
        }
        $searchResult = $records;
    }
    $searchAttempted = true;
}

// 🔐 BULLETPROOF BACKEND FALLBACK FILTER: Cleans up the image URL string manually before drawing the img tags
$displayPhotoUrl = 'https://placehold.co';
if ($searchResult && is_array($searchResult) && !empty($searchResult['cid_photo'])) {
    $rawPath = $searchResult['cid_photo'];
    
    if (strpos($rawPath, 'uploads/') !== false) {
        $cleanUploadPath = substr($rawPath, strpos($rawPath, 'uploads/'));
        $displayPhotoUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $cleanUploadPath;
    } else {
        $displayPhotoUrl = str_replace('http://', 'https://', $rawPath);
    }
}
?>

<?php include 'header.php'; ?>

<div class="beautiful-card mx-auto animate-fade-in shadow-lg" style="max-width: 600px;">
    <div class="card-header-gradient text-center">
        <h4 class="m-0 fw-bold" style="color: white; padding: 0.5rem 0;">👮 Gate 2: Security Verification Desk</h4>
    </div>
    
    <div class="p-4 bg-white">
        <?php if ($updateMessage): ?>
            <div class="alert alert-success py-2.5 px-3 rounded-3 small fw-semibold mb-4 text-start"><?php echo $updateMessage; ?></div>
        <?php endif; ?>

        <!-- Search Box Console Input Form Configuration layout -->
        <form method="GET" action="gate2.php" class="mb-4">
            <label class="form-label custom-label-style text-start d-block">Scan or Enter Visitor National CID</label>
            <div class="input-group">
                <input type="text" name="search" class="form-control custom-input-style" placeholder="Type CID number to parse query logs..." value="<?php echo htmlspecialchars($searchQuery); ?>" required>
                <button type="submit" class="btn text-white px-4 fw-bold" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 0 10px 10px 0 !important;">Verify</button>
            </div>
        </form>

        <?php if ($searchAttempted): ?>
            <?php if ($searchResult && is_array($searchResult) && isset($searchResult['visitor_name'])): ?>
                <!-- Visitor Record Log Data Sheet Found Viewport Frame Box Container -->
                <div class="section-container bg-light border p-3 rounded-3 animate-fade-in text-start">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 border-bottom pb-2">
                        <span class="fw-bold text-dark small">Ecosystem Transaction Logs Grid</span>
                        <?php 
                            $status = $searchResult['status'] ?? 'Pending';
                            $badgeClass = 'bg-warning text-dark';
                            if ($status === 'Checked-In') $badgeClass = 'bg-success text-white';
                            if ($status === 'Checked-Out') $badgeClass = 'bg-danger text-white';
                        ?>
                        <span class="badge <?php echo $badgeClass; ?> px-2 py-1.5 rounded small"><?php echo $status; ?></span>
                    </div>

                    <div class="text-center mb-3">
                        <div class="mx-auto border p-1 rounded bg-white shadow-sm mb-2" style="width: 150px; height: 180px; overflow: hidden;">
                            <img src="<?php echo htmlspecialchars($displayPhotoUrl); ?>" class="w-100 h-100" style="object-fit: cover;" alt="Visitor Pass Profile ID Document">
                        </div>
                        <h5 class="fw-bold text-dark m-0"><?php echo htmlspecialchars($searchResult['visitor_name']); ?></h5>
                        <small class="font-monospace text-muted">CID Reference No: <?php echo htmlspecialchars($searchResult['visitor_cid']); ?></small>
                    </div>

                    <div class="row row-cols-2 g-2 text-start small border-top pt-2">
                        <div><span class="text-secondary d-block">Classification</span><strong>💼 <?php echo htmlspecialchars($searchResult['visitor_type']); ?></strong></div>
                        <div><span class="text-secondary d-block">Target Inmate CID</span><strong>🆔 <?php echo htmlspecialchars($searchResult['inmate_cid'] ?? 'N/A (Official)'); ?></strong></div>
                        <div class="mt-2"><span class="text-secondary d-block">Inmate Full Name</span><strong>👤 <?php echo htmlspecialchars($searchResult['inmate_name'] ?? 'N/A'); ?></strong></div>
                        <div class="mt-2"><span class="text-secondary d-block">Target Cell Location</span><strong>🏢 <?php echo htmlspecialchars($searchResult['block'] ?? 'N/A'); ?></strong></div>
                    </div>

                    <!-- Linked accompanying visitors roster scanner engine wrapper row loops mapping -->
                    <?php if (!empty($searchResult['accompanying_data'])): ?>
                        <?php $accList = json_decode($searchResult['accompanying_data'], true); ?>
                        <?php if (!empty($accList) && is_array($accList)): ?>
                            <div class="mt-3 border-top pt-2 text-start">
                                <span class="text-secondary d-block small mb-1">Linked Passengers Roster:</span>
                                <div class="bg-white p-2 border rounded-3 small" style="max-height: 120px; overflow-y: auto;">
                                    <?php foreach ($accList as $acc): ?>
                                        <div class="d-flex justify-content-between font-monospace border-bottom py-1 text-dark" style="font-size: 0.8rem;">
                                            <span>👥 <?php echo htmlspecialchars($acc['name'] ?? ''); ?></span>
                                            <span class="text-muted">CID: <?php echo htmlspecialchars($acc['cid'] ?? ''); ?> (<?php echo htmlspecialchars($acc['relation'] ?? ''); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Action Operation Context Toggles Button Controls base setup components mapping rules -->
                    <?php if ($status !== 'Checked-Out'): ?>
                        <form method="POST" action="gate2.php" class="mt-3 pt-2 border-top">
                            <input type="hidden" name="action_id" value="<?php echo $searchResult['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $status; ?>">
                            
                            <?php if ($status === 'Pending'): ?>
                                <button type="submit" class="btn btn-success w-100 fw-bold py-2" style="border-radius: 10px;">
                                    🔒 Validate Token &amp; Execute Check-In Log
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-danger w-100 fw-bold py-2" style="border-radius: 10px;">
                                    🔓 Terminate Authorization Pass &amp; Check-Out
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center small fw-semibold m-0 mt-3 py-2 rounded-3">
                            ⛔ This security pass clearance cycle has already terminated.
                        </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <!-- No Record Match Output Warning Notice Screen Wrapper -->
                <div class="alert alert-danger text-center small fw-semibold m-0 py-3 rounded-3 animate-fade-in shadow-sm">
                    🔍 ACCESS REJECTED: No verified Gate 1 registration records found matching that Visitor CID reference token parameter.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
</body>
</html>
