<?php
// Initialize session cleanly at the absolute top of the stack
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Guard: Restrict access strictly to the Admin Account role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$inmateCid = isset($_GET['inmateCid']) ? $_GET['inmateCid'] : '';
$filterType = isset($_GET['filterType']) ? $_GET['filterType'] : '';

// 🚀 EXCEL CSV DATA LOG EXPORT HOOK INTERCEPTOR
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Re-run database search metrics to catch identical current filter parameter records
    $exportQuery = "SELECT * FROM visitors WHERE 1=1";
    $exportParams = [];

    if ($filterType === 'daily') { $exportQuery .= " AND date(registeredAt) = date('now')"; } 
    elseif ($filterType === 'weekly') { $exportQuery .= " AND registeredAt >= datetime('now', '-7 days')"; } 
    elseif ($filterType === 'monthly') { $exportQuery .= " AND registeredAt >= datetime('now', '-30 days')"; }

    if (!empty($startDate) && !empty($endDate)) {
        $exportQuery .= " AND date(registeredAt) BETWEEN date(?) AND date(?)";
        $exportParams[] = $startDate; $exportParams[] = $endDate;
    }
    if (!empty($inmateCid)) { $exportQuery .= " AND inmateCid = ?"; $exportParams[] = $inmateCid; }
    $exportQuery .= " ORDER BY registeredAt DESC";

    $exportStmt = $db->prepare($exportQuery);
    $exportStmt->execute($exportParams);
    $exportRecords = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    // Set browser attachment headers to download as CSV layout data spreadsheet sheet file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=VRS_System_Log_Export_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Write CSV Sheet Header Titles Line
    fputcsv($output, ['Registered At', 'Visitor Name', 'Visitor CID', 'Classification Type', 'Inmate Name', 'Inmate CID', 'Cell Block', 'Relationship', 'Status', 'Verified At']);
    
    foreach ($exportRecords as $row) {
        fputcsv($output, [
            $row['registeredAt'],
            $row['visitorName'],
            $row['visitorCid'],
            $row['visitorType'],
            $row['inmateName'] ?? 'N/A',
            $row['inmateCid'] ?? 'N/A',
            $row['block'] ?? 'N/A',
            $row['relationship'] ?? 'N/A',
            $row['status'],
            $row['verifiedAt'] ?? 'Pending'
        ]);
    }
    fclose($output);
    exit;
}

// 📊 MAIN BOARD WORKSPACE RECORD VISUALIZATION PROCESSOR
$queryString = "SELECT * FROM visitors WHERE 1=1";
$params = [];

if ($filterType === 'daily') {
    $queryString .= " AND date(registeredAt) = date('now')";
} elseif ($filterType === 'weekly') {
    $queryString .= " AND registeredAt >= datetime('now', '-7 days')";
} elseif ($filterType === 'monthly') {
    $queryString .= " AND registeredAt >= datetime('now', '-30 days')";
}

if (!empty($startDate) && !empty($endDate)) {
    $queryString .= " AND date(registeredAt) BETWEEN date(?) AND date(?)";
    $params[] = $startDate;
    $params[] = $endDate;
}

if (!empty($inmateCid)) {
    $queryString .= " AND inmateCid = ?";
    $params[] = $inmateCid;
}

$queryString .= " ORDER BY registeredAt DESC";
$stmt = $db->prepare($queryString);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<style>
    .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .dashboard-panel-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.04);
        padding: 1.5rem;
    }
    .custom-table {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .custom-table th {
        background-color: #0f172a !important;
        color: #ffffff !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem;
    }
    .custom-table td {
        padding: 1rem;
        font-size: 0.875rem;
        vertical-align: middle;
        background-color: #ffffff;
    }
    .btn-excel {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        color: white;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .btn-excel:hover {
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        color: white;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="w-100 py-2 py-md-4 animate-fade-in">
    <!-- Workspace Main Header Banner Line Layout -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold text-dark m-0" style="letter-spacing: -0.5px;">Admin Command Control Workspace</h3>
            <p class="text-secondary small m-0">Monitor ecosystem timeline metrics, query visitor assignment clusters, and export auditing spreadsheets.</p>
        </div>
        <!-- 💡 One-Click Excel Logging Export Trigger Button -->
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-excel px-3 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm small">
            📊 Export Sheet to Excel (.CSV)
        </a>
    </div>

    <!-- Parameter Filter Form Control Card -->
    <div class="dashboard-panel-card mb-4">
        <h5 class="fw-bold text-dark mb-3">Query Analysis Parameters</h5>
        <form method="GET" action="admin.php">
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Start Date Range</label>
                    <input type="date" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>" class="form-control py-2 shadow-sm small">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">End Date Range</label>
                    <input type="date" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>" class="form-control py-2 shadow-sm small">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter Inmate CID</label>
                    <input type="text" name="inmateCid" value="<?php echo htmlspecialchars($inmateCid); ?>" class="form-control py-2 shadow-sm small" placeholder="Enter target card ID">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary py-2 flex-grow-1 fw-bold shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">Apply Filter</button>
                    <a href="admin.php" class="btn btn-outline-secondary py-2 px-3 fw-bold">Reset</a>
                </div>
            </div>

            <!-- Timeline Preset Button Selector Block -->
            <div class="d-flex flex-wrap gap-1.5 mt-3 pt-3 border-top">
                <button type="submit" name="filterType" value="daily" class="btn btn-sm <?php echo $filterType === 'daily' ? 'btn-dark' : 'btn-outline-dark'; ?> px-3 rounded-pill">Today</button>
                <button type="submit" name="filterType" value="weekly" class="btn btn-sm <?php echo $filterType === 'weekly' ? 'btn-dark' : 'btn-outline-dark'; ?> px-3 rounded-pill">Past 7 Days</button>
                <button type="submit" name="filterType" value="monthly" class="btn btn-sm <?php echo $filterType === 'monthly' ? 'btn-dark' : 'btn-outline-dark'; ?> px-3 rounded-pill">Past 30 Days</button>
            </div>
        </form>
    </div>

    <!-- Data Monitoring Logging Grid Table Component Layout Box -->
    <div class="table-responsive custom-table shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Registered At</th>
                    <th>Visitor (CID)</th>
                    <th>Classification Type</th>
                    <th>Inmate Target (CID)</th>
                    <th>Cell Block</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($records) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted bg-white border-0">
                            <h6 class="fw-bold m-0">No matching system logs match specified filter parameters.</h6>
                        </td>
                    </tr>
                <?php endif; ?>
                
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td class="font-monospace text-secondary small"><?php echo $row['registeredAt']; ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['visitorName']); ?></div>
                            <small class="text-muted font-monospace">CID: <?php echo htmlspecialchars($row['visitorCid']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1.5 small"><?php echo $row['visitorType']; ?> Visit</span>
                        </td>
                        <td>
                            <?php if ($row['visitorType'] === 'Others'): ?>
                                <span class="text-muted small">N/A (External Profile)</span>
                            <?php else: ?>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['inmateName'] ?? ''); ?></div>
                                <small class="text-muted font-monospace">CID: <?php echo htmlspecialchars($row['inmateCid'] ?? ''); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['visitorType'] === 'Others'): ?>
                                <span class="text-muted small">N/A</span>
                            <?php else: ?>
                                <span class="badge bg-secondary font-normal px-2 py-1"><?php echo htmlspecialchars($row['block'] ?? 'N/A'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php 
                            $status = $row['status'];
                            $badgeClass = 'bg-warning text-dark';
                            if ($status === 'Verified') { $badgeClass = 'bg-success text-white'; }
                            elseif ($status === 'Rejected') { $badgeClass = 'bg-danger text-white'; }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?> px-2.5 py-1.5 rounded-pill fw-bold text-uppercase small" style="font-size:0.7rem; letter-spacing:0.5px;">
                                <?php echo $status; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
