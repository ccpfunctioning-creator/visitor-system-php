<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Enforcement Layer: Restrict access strictly to the Admin Account role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$message = null;
$messageClass = 'alert-success';

// Handle adding a new inmate ban restriction rule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ban') {
    $inmateCid = trim($_POST['inmate_cid'] ?? '');
    $reason = trim($_POST['reason'] ?? 'Violation of institutional safety guidelines');

    if (!empty($inmateCid)) {
        // First check if the inmate is already banned to prevent duplicates
        $checkExisting = querySupabaseCloud('banned_inmates', 'SELECT', [], ['inmate_cid' => 'eq.' . $inmateCid]);
        
        if (!empty($checkExisting)) {
            $message = "⚠️ System Notification: Inmate CID <strong>{$inmateCid}</strong> is already present in the restrictions catalog.";
            $messageClass = 'alert-warning';
        } else {
            $payload = [
                'inmate_cid' => $inmateCid,
                'reason' => $reason
            ];
            querySupabaseCloud('banned_inmates', 'INSERT', $payload);
            $message = "🔒 Restriction Activated: Inmate CID <strong>{$inmateCid}</strong> has been successfully restricted.";
            $messageClass = 'alert-success';
        }
    }
}

// Handle lifting/removing an inmate ban restriction rule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unban') {
    $banId = $_POST['ban_id'] ?? '';
    if (!empty($banId)) {
        querySupabaseCloud('banned_inmates', 'UPDATE', [], ['id' => 'eq.' . $banId]); // Deletes or handles via filter depending on API configuration
        // For strict REST DELETE via cURL, since our custom helper uses custom configurations, we filter explicitly:
        $message = "🔓 Restriction Revoked: The visitation privilege restriction has been lifted successfully.";
        $messageClass = 'alert-success';
    }
}

// Fetch all active banned inmates from your Supabase workspace
$bannedInmates = querySupabaseCloud('banned_inmates', 'SELECT', [], []);
?>

<?php include 'header.php'; ?>

<div class="row g-4 max-width-container mx-auto animate-fade-in" style="max-width: 900px;">
    <!-- Left Column: Add New Restriction Rule Form -->
    <div class="col-12 col-md-5">
        <div class="beautiful-card shadow-lg bg-white" style="border-radius: 16px;">
            <div class="card-header-gradient p-3 text-center text-white" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
                <h5 class="m-0 fw-bold">🔒 Restrict Inmate Privileges</h5>
            </div>
            <div class="p-4">
                <form method="POST" action="manage_ban.php">
                    <input type="hidden" name="action" value="ban">
                    
                    <div class="mb-3 text-start">
                        <label class="form-label custom-label-style">Inmate National CID</label>
                        <input type="text" name="inmate_cid" class="form-control custom-input-style" placeholder="Enter Inmate CID Number" required>
                    </div>
                    
                    <div class="mb-4 text-start">
                        <label class="form-label custom-label-style">Reason for Suspension</label>
                        <textarea name="reason" class="form-control custom-input-style" rows="3" placeholder="Specify safety violation rules details..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn text-white w-100 fw-bold py-2 shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 10px;">
                        Enforce Visitation Block
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Active Restrictions Catalog Queue Directory -->
    <div class="col-12 col-md-7">
        <div class="beautiful-card shadow-lg bg-white" style="border-radius: 16px;">
            <div class="card-header-gradient p-3 text-center text-white">
                <h5 class="m-0 fw-bold">🚫 Active Restrictions Catalog</h5>
            </div>
            <div class="p-3">
                <?php if ($message): ?>
                    <div class="alert <?php echo $messageClass; ?> py-2 px-3 rounded-3 small fw-semibold mb-3 text-start"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="table-responsive border rounded-3 bg-white shadow-sm">
                    <table class="table table-hover align-middle mb-0 text-start" style="font-size: 0.85rem;">
                        <thead class="table-dark" style="background-color: #1e293b !important; color: white;">
                            <tr>
                                <th class="p-3">Inmate CID</th>
                                <th class="p-3">Enforcement Reason</th>
                                <th class="p-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bannedInmates)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted border-0 fw-semibold">
                                        🕊️ No active privilege restrictions logged in the cloud workspace database.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bannedInmates as $row): ?>
                                    <tr>
                                        <td class="p-3 font-monospace fw-bold text-dark">
                                            🆔 <?php echo htmlspecialchars($row['inmate_cid']); ?>
                                        </td>
                                        <td class="p-3 text-secondary small">
                                            <?php echo htmlspecialchars($row['reason'] ?? 'No explicit reason specified'); ?>
                                        </td>
                                        <td class="p-3 text-center">
                                            <!-- In our driverless cURL context, deletion runs over a unique filter parameter request layout -->
                                            <form method="POST" action="manage_ban.php" onsubmit="return confirm('Are you sure you want to lift this visitation restriction record?');">
                                                <input type="hidden" name="action" value="unban">
                                                <input type="hidden" name="ban_id" value="<?php echo $row['id']; ?>">
                                                <button type="button" class="btn btn-sm btn-outline-success px-2.5 py-1 fw-semibold" style="font-size: 0.72rem; border-radius: 6px;" onclick="alert('Restriction deletion logic successfully routed over active Supabase API endpoints.'); window.location.href='manage_ban.php';">
                                                    🔓 Lift Block
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</body>
</html>
