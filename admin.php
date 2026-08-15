<?php
require_once 'db.php';

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$inmateCid = isset($_GET['inmateCid']) ? $_GET['inmateCid'] : '';
$filterType = isset($_GET['filterType']) ? $_GET['filterType'] : '';

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

<?php include 'templates/header.php'; ?>
<div class="w-100">
    <h3>Admin Command Control Workspace</h3>
    <div class="card p-3 my-4 border-0 shadow-sm bg-white">
        <h5>Query Analysis Parameters</h5>
        <form method="GET" action="admin.php" class="row g-3">
            <div class="col-md-3"><label class="form-label">Start Date</label><input type="date" name="startDate" value="<?php echo $startDate; ?>" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">End Date</label><input type="date" name="endDate" value="<?php echo $endDate; ?>" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Filter Inmate CID</label><input type="text" name="inmateCid" value="<?php echo $inmateCid; ?>" class="form-control" placeholder="Enter CID"></div>
            <div class="col-md-3 d-flex align-items-end gap-1"><button type="submit" class="btn btn-primary flex-grow-1">Filter</button><a href="admin.php" class="btn btn-secondary">Reset</a></div>
            <div class="col-12 mt-2">
                <button type="submit" name="filterType" value="daily" class="btn btn-sm btn-outline-dark">Today</button>
                <button type="submit" name="filterType" value="weekly" class="btn btn-sm btn-outline-dark">Past 7 Days</button>
                <button type="submit" name="filterType" value="monthly" class="btn btn-sm btn-outline-dark">Past 30 Days</button>
            </div>
        </form>
    </div>

    <div class="table-responsive bg-white rounded shadow-sm p-3">
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr><th>Registered At</th><th>Visitor (CID)</th><th>Type</th><th>Inmate Target (CID)</th><th>Block</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if(count($records) === 0): ?><tr><td colspan="6" class="text-center py-3">No system logs match specified filter metrics.</td></tr><?php endif; ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?php echo $row['registeredAt']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['visitorName']); ?></strong> (<?php echo htmlspecialchars($row['visitorCid']); ?>)</td>
                        <td><span class="badge bg-secondary"><?php echo $row['visitorType']; ?></span></td>
                        <td><?php echo ($row['visitorType'] === 'Others') ? 'N/A' : htmlspecialchars($row['inmateName']) . ' ('.htmlspecialchars($row['inmateCid']).')'; ?></td>
                        <td><?php echo $row['block'] ? $row['block'] : 'N/A'; ?></td>
                        <td><span class="badge <?php echo $row['status'] === 'Verified' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo $row['status']; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
