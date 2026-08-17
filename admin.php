<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$searchQuery = trim($_GET['search'] ?? '');
$filterType = $_GET['filter_type'] ?? 'all';

$filterParams = [];
if (!empty($searchQuery)) {
    $filterParams['visitor_cid'] = 'eq.' . $searchQuery;
}

$allVisitors = querySupabaseCloud('visitors', 'SELECT', [], $filterParams);

if ($filterType !== 'all' && is_array($allVisitors)) {
    $allVisitors = array_filter($allVisitors, function($item) use ($filterType) {
        return isset($item['status']) && $item['status'] === $filterType;
    });
}

$totalRecords = is_array($allVisitors) ? count($allVisitors) : 0;
$pendingCount = 0;
$checkedInCount = 0;
$checkedOutCount = 0;

if (is_array($allVisitors)) {
    foreach ($allVisitors as $v) {
        $status = $v['status'] ?? 'Pending';
        if ($status === 'Pending') $pendingCount++;
        if ($status === 'Checked-In') $checkedInCount++;
        if ($status === 'Checked-Out') $checkedOutCount++;
    }
}
?>

<?php include 'header.php'; ?>

<div class="beautiful-card mx-auto animate-fade-in shadow-lg" style="max-width: 900px; background: #ffffff; border-radius: 16px;">
    <div class="card-header-gradient text-center py-4">
        <h4 class="m-0 fw-bold" style="color: white;">📊 Central Security Administration Management</h4>
        <p class="text-white-50 m-0 small mt-1">Real-time status overview and terminal history metrics sync from Supabase Cloud</p>
    </div>
    
    <div class="p-3 p-md-4">
        <div class="row g-3 mb-4 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light border rounded-3">
                    <span class="text-secondary small d-block fw-semibold uppercase mb-1">Total Cycles</span>
                    <h3 class="fw-bold text-dark m-0"><?php echo $totalRecords; ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded-3" style="background-color: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2) !important;">
                    <span class="text-warning small d-block fw-semibold uppercase mb-1">⏳ Pending</span>
                    <h3 class="fw-bold text-warning m-0"><?php echo $pendingCount; ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded-3" style="background-color: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2) !important;">
                    <span class="text-success small d-block fw-semibold uppercase mb-1">👮 Checked-In</span>
                    <h3 class="fw-bold text-success m-0"><?php echo $checkedInCount; ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded-3" style="background-color: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2) !important;">
                    <span class="text-danger small d-block fw-semibold uppercase mb-1">🛑 Checked-Out</span>
                    <h3 class="fw-bold text-danger m-0"><?php echo $checkedOutCount; ?></h3>
                </div>
            </div>
        </div>

        <form method="GET" action="admin.php" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label custom-label-style">Search Visitor CID Token</label>
                <input type="text" name="search" class="form-control custom-input-style py-2" placeholder="Enter Exact CID Number..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label custom-label-style">Filter status State</label>
                <select name="filter_type" class="form-select custom-input-style py-2">
                    <option value="all" <?php if($filterType === 'all') echo 'selected'; ?>>All Entries Directory</option>
                    <option value="Pending" <?php if($filterType === 'Pending') echo 'selected'; ?>>⏳ Pending</option>
                    <option value="Checked-In" <?php if($filterType === 'Checked-In') echo 'selected'; ?>>👮 Checked-In</option>
                    <option value="Checked-Out" <?php if($filterType === 'Checked-Out') echo 'selected'; ?>>🛑 Checked-Out</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn text-white w-100 fw-bold py-2 shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; border-radius: 10px;">
                    🔍 Apply Filters
                </button>
            </div>
        </form>

        <div class="table-responsive border rounded-3 shadow-sm bg-white">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-dark" style="background-color: #0f172a !important; color: white;">
                    <tr>
                        <th class="p-3">Snapshot Profile</th>
                        <th class="p-3">Primary Visitor Data</th>
                        <th class="p-3">Target Inmate Data</th>
                        <th class="p-3 text-center">Authorization Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allVisitors)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted border-0 fw-semibold">
                                📭 No verified structural log entries match your filtering queries inside the database.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allVisitors as $row): ?>
                            <?php
                                // 💡 Dynamic link format parser for Admin view
                                $adminPhotoUrl = 'https://placehold.co';
                                if (!empty($row['cid_photo'])) {
                                    $rawAdminPath = $row['cid_photo'];
                                    if (strpos($rawAdminPath, 'uploads/') !== false) {
                                        $cleanAdminPath = substr($rawAdminPath, strpos($rawAdminPath, 'uploads/'));
                                        $adminPhotoUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $cleanAdminPath;
                                    } else {
                                        $adminPhotoUrl = str_replace('http://', 'https://', $rawAdminPath);
                                    }
                                }
                            ?>
                            <tr>
                                <td class="p-3">
                                    <div class="border rounded bg-light overflow-hidden shadow-sm" style="width: 55px; height: 65px;">
                                        <img src="<?php echo htmlspecialchars($adminPhotoUrl); ?>" class="w-100 h-100" style="object-fit: cover;">
                                    </div>
                                </td>
                                <td class="p-3">
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($row['visitor_name']); ?></strong>
                                    <span class="font-monospace text-secondary d-block" style="font-size: 0.75rem;">CID: <?php echo htmlspecialchars($row['visitor_cid']); ?></span>
                                    <span class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;"><?php echo htmlspecialchars($row['visitor_type']); ?></span>
                                </td>
                                <td class="p-3">
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($row['inmate_name'] ?? 'N/A (Official)'); ?></strong>
                                    <span class="text-secondary d-block" style="font-size: 0.75rem;">Block: <?php echo htmlspecialchars($row['block'] ?? 'N/A'); ?></span>
                                    <span class="text-muted d-block" style="font-size: 0.72rem;">Relation: <?php echo htmlspecialchars($row['relationship'] ?? 'N/A'); ?></span>
                                </td>
                                <td class="p-3 text-center">
                                    <?php 
                                        $currStatus = $row['status'] ?? 'Pending';
                                        $badgeClass = 'bg-warning text-dark';
                                        if ($currStatus === 'Checked-In') $badgeClass = 'bg-success text-white';
                                        if ($currStatus === 'Checked-Out') $badgeClass = 'bg-danger text-white';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> px-2.5 py-1.5 rounded fw-bold" style="font-size: 0.75rem;"><?php echo $currStatus; ?></span>
                                    <span class="text-muted d-block font-monospace mt-1" style="font-size: 0.68rem;">
                                        <?php echo date('m/d H:i', strtotime($row['registered_at'] ?? 'now')); ?>
                                    </span>
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
</body>
</html>
