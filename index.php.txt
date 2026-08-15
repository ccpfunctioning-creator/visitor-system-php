<?php
require_once 'db.php';

$successData = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitorType = $_POST['visitorType'];
    $visitorName = $_POST['visitorName'];
    $visitorCid = $_POST['visitorCid'];
    
    $inmateCid = ($visitorType === 'Others') ? null : $_POST['inmateCid'];
    $inmateName = ($visitorType === 'Others') ? null : $_POST['inmateName'];
    $block = ($visitorType === 'Others') ? null : $_POST['block'];
    $relationship = ($visitorType === 'Others') ? null : $_POST['relationship'];

    // Enforce backend check against banned inmates
    if ($visitorType !== 'Others' && !empty($inmateCid)) {
        $checkBan = $db->prepare("SELECT COUNT(*) FROM banned_inmates WHERE inmateCid = ?");
        $checkBan->execute([$inmateCid]);
        if ($checkBan->fetchColumn() > 0) {
            $errorMessage = "Registration Blocked: This inmate's visitation privileges are currently suspended.";
        }
    }

    // Process file upload securely
    $photoPath = '';
    if (!$errorMessage && isset($_FILES['cidPhoto']) && $_FILES['cidPhoto']['error'] === 0) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) { mkdir($targetDir, 0777, true); }
        $fileName = "CID-" . time() . "_" . basename($_FILES['cidPhoto']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['cidPhoto']['tmp_name'], $targetFilePath)) {
            $photoPath = $targetFilePath;
        } else {
            $errorMessage = "Failed to upload identification document photo.";
        }
    }

    if (!$errorMessage) {
        $stmt = $db->prepare("INSERT INTO visitors (inmateName, inmateCid, block, visitorName, visitorCid, relationship, visitorType, cidPhoto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$inmateName, $inmateCid, $block, $visitorName, $visitorCid, $relationship, $visitorType, $photoPath]);
        
        $lastId = $db->lastInsertId();
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $verificationUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/gate2.php?searchId=" . $lastId;
        
        // Use a secure high-resolution open source API layout link to produce QR code directly without heavy libraries
        $qrImage = "https://qrserver.com" . urlencode($verificationUrl);
        
        $successData = ['qr' => $qrImage, 'name' => $visitorName, 'cid' => $visitorCid, 'type' => $visitorType];
    }
}
?>

<?php include 'templates/header.php'; ?>

<?php if ($successData): ?>
    <!-- Render Success Ticket Generation Framework Container Layout -->
    <div class="text-center p-5 bg-white shadow rounded col-md-6 mx-auto card border-0">
        <div class="alert alert-success"><h3>Registration Complete</h3></div>
        <p>Please present this generated entry pass token ticket at Gate 2 verification desk.</p>
        <img src="<?php echo $successData['qr']; ?>" class="img-fluid border p-3 my-3 mx-auto bg-light" style="max-width:220px;" alt="Gate Pass Token">
        <h4 class="mt-2"><?php echo htmlspecialchars($successData['name']); ?></h4>
        <p class="text-muted">CID: <?php echo htmlspecialchars($successData['cid']); ?> | Classification: <?php echo $successData['type']; ?></p>
        <a href="index.php" class="btn btn-primary mt-3">Register New Entry</a>
    </div>
<?php else: ?>
    <div class="beautiful-card">
        <div class="card-header-gradient"><h4>Gate 1: Entry Registration Form</h4></div>
        <div class="p-4 bg-white">
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label">Visitor Classification Type</label>
                    <select name="visitorType" id="visitorType" class="form-select" required>
                        <option value="Personal">Personal Visit</option>
                        <option value="Official">Official Business</option>
                        <option value="Conjugal">Conjugal Visit</option>
                        <option value="Night Visitor">Night Visitor</option>
                        <option value="Others">Others (Hides Inmate Parameters)</option>
                    </select>
                </div>

                <div id="inmateSection" class="section-container">
                    <div class="form-section-title">Inmate Target Parameters</div>
                    <div class="mb-3">
                        <label class="form-label">Inmate CID No. (Type 111222333 to test ban alert)</label>
                        <input type="text" name="inmateCid" id="inmateCid" class="form-control">
                        <div id="banStatus" class="alert alert-danger p-2 mt-2 small fw-bold d-none"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Inmate Name</label><input type="text" name="inmateName" class="form-control target-field"></div>
                    <div class="mb-3">
                        <label class="form-label">Block Assignment Location</label>
                        <select name="block" class="form-select target-field">
                            <option value="Block I">Block I</option><option value="Block II">Block II</option><option value="Block III">Block III</option><option value="Block IV">Block IV</option>
                        </select>
                    </div>
                    <div><label class="form-label">Relationship with Inmate</label><input type="text" name="relationship" class="form-control target-field"></div>
                </div>

                <div class="section-container">
                    <div class="form-section-title">Visitor Identification Profile</div>
                    <div class="mb-3"><label class="form-label">Visitor Full Name</label><input type="text" name="visitorName" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Visitor CID Card Number</label><input type="text" name="visitorCid" class="form-control" required></div>
                    <div class="mb-0"><label class="form-label">Upload Official CID Photo Document</label><input type="file" name="cidPhoto" class="form-control" accept="image/*" required></div>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-gradient w-100">Submit Application & Generate Pass</button>
            </form>
        </div>
    </div>

    <script>
        const typeSelect = document.getElementById('visitorType');
        const inmateSection = document.getElementById('inmateSection');
        const inmateCid = document.getElementById('inmateCid');
        const submitBtn = document.getElementById('submitBtn');
        const banStatus = document.getElementById('banStatus');

        typeSelect.addEventListener('change', function() {
            if(this.value === 'Others') {
                inmateSection.style.display = 'none';
                inmateSection.querySelectorAll('input, select').forEach(el => { el.removeAttribute('required'); el.value = ''; });
                submitBtn.disabled = false;
                banStatus.classList.add('d-none');
            } else {
                inmateSection.style.display = 'block';
                inmateSection.querySelectorAll('.target-field, #inmateCid').forEach(el => el.setAttribute('required', 'true'));
            }
        });

        inmateCid.addEventListener('blur', async function() {
            if(!this.value || typeSelect.value === 'Others') return;
            const res = await fetch('check_ban.php?cid=' + this.value);
            const data = await res.json();
            if(data.banned) {
                banStatus.textContent = "⚠️ ACCESS DENIED: This inmate's visitation privileges are currently suspended.";
                banStatus.classList.remove('d-none');
                submitBtn.disabled = true;
            } else {
                banStatus.classList.add('d-none');
                submitBtn.disabled = false;
            }
        });
    </script>
<?php endif; ?>
</body>
</html>
