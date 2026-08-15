<?php
// Initialize session at the absolute top of the processing pipeline
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

$successData = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitorType = $_POST['visitorType'] ?? 'Personal';
    $visitorName = $_POST['visitorName'] ?? '';
    $visitorCid = $_POST['visitorCid'] ?? '';
    
    $inmateCid = ($visitorType === 'Others') ? null : ($_POST['inmateCid'] ?? '');
    $inmateName = ($visitorType === 'Others') ? null : ($_POST['inmateName'] ?? '');
    $block = ($visitorType === 'Others') ? null : ($_POST['block'] ?? '');
    $relationship = ($visitorType === 'Others') ? null : ($_POST['relationship'] ?? '');

    // Backend verification filter logic checking restricted individuals
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
        
        // Robust layout mapping to isolate public server location path boundaries
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $verificationUrl = $protocol . $host . "/gate2.php?searchId=" . $lastId;
        
        // High-resolution API generation configuration parameters link
        $qrImage = "https://qrserver.com" . urlencode($verificationUrl);
        
        $successData = ['qr' => $qrImage, 'name' => $visitorName, 'cid' => $visitorCid, 'type' => $visitorType];
    }
}
?>

<?php include 'header.php'; ?>

<?php if ($successData): ?>
    <!-- Premium Centered Glassmorphic Ticket Rendering Architecture Layout Panel -->
    <div class="beautiful-card mx-auto my-4 text-center" style="max-width: 520px;">
        <div class="card-header-gradient py-4">
            <h4 class="m-0 fw-bold">✨ Access Token Generated</h4>
        </div>
        <div class="p-5 bg-white d-flex flex-column align-items-center">
            <div class="alert alert-success px-4 py-2 w-100 rounded-3 mb-4 fw-semibold small">
                Visitor Registration Logged Successfully
            </div>
            
            <p class="text-secondary small mb-4">Please present this secure verification QR code below to the security officer on desk duty at Gate 2.</p>
            
            <!-- QR Frame Box Component -->
            <div class="p-3 bg-light border rounded-4 mb-4 shadow-sm d-flex align-items-center justify-content-center" style="width: 250px; height: 250px;">
                <img src="<?php echo $successData['qr']; ?>" class="img-fluid rounded-3" style="width: 220px; height: 220px;" alt="Verification QR Code">
            </div>
            
            <h4 class="fw-bold mb-1 mt-2 text-dark"><?php echo htmlspecialchars($successData['name']); ?></h4>
            <div class="d-flex gap-2 justify-content-center align-items-center mb-4">
                <span class="badge bg-light text-secondary border px-2 py-1 small">CID: <?php echo htmlspecialchars($successData['cid']); ?></span>
                <span class="badge bg-primary px-2 py-1 small"><?php echo $successData['type']; ?> Visit</span>
            </div>
            
            <hr class="w-100 text-muted my-3">
            
            <a href="index.php" class="btn btn-gradient w-100 py-2.5 mt-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; color: white;">
                Register Another Visitor
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Registration Entry Form Container Wrapper Panel -->
    <div class="beautiful-card mx-auto">
        <div class="card-header-gradient">
            <h4 class="m-0 fw-bold">Gate 1: Entry Registration Form</h4>
        </div>
        <div class="p-4 bg-white">
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger p-2.5 small rounded-3"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label">Visitor Classification Type</label>
                    <select name="visitorType" id="visitorType" class="form-select" required>
                        <option value="Personal">Personal Visit</option>
                        <option value="Official">Official Business</option>
                        <option value="Conjugal">Conjugal Visit</option>
                        <option value="Night Visitor">Night Visitor</option>
                        <option value="Others">Others (Non-Inmate Base)</option>
                    </select>
                </div>

                <div id="inmateSection" class="section-container">
                    <div class="form-section-title">Inmate Target Parameters</div>
                    <div class="mb-3">
                        <label class="form-label">Inmate CID No. <span class="text-muted font-monospace small">(111222333 to test alert)</span></label>
                        <input type="text" name="inmateCid" id="inmateCid" class="form-control" placeholder="Enter Inmate CID Card Number">
                        <div id="banStatus" class="alert alert-danger p-2 mt-2 small fw-bold d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Inmate Full Name</label>
                        <input type="text" name="inmateName" class="form-control target-field" placeholder="Enter Inmate Full Name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Block Assignment Location</label>
                        <select name="block" class="form-select target-field">
                            <option value="Block I">Block I</option>
                            <option value="Block II">Block II</option>
                            <option value="Block III">Block III</option>
                            <option value="Block IV">Block IV</option>
                            <option value="Block V">Block V</option>
                            <option value="Block VI">Block VI</option>
                            <option value="Block VII">Block VII</option>
                            <option value="Block VIII">Block VIII</option>
                            <option value="Block IX">Block IX</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Relationship with Inmate</label>
                        <input type="text" name="relationship" class="form-control target-field" placeholder="e.g. Spouse, Sibling, Legal Counsel">
                    </div>
                </div>

                <div class="section-container">
                    <div class="form-section-title">Visitor Identification Profile</div>
                    <div class="mb-3">
                        <label class="form-label">Visitor Full Name</label>
                        <input type="text" name="visitorName" class="form-control" placeholder="Enter your official full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visitor CID Card Number</label>
                        <input type="text" name="visitorCid" class="form-control" placeholder="Enter your CID number" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Upload Official CID Photo Document</label>
                        <input type="file" name="cidPhoto" class="form-control" accept="image/*" required>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-gradient w-100 py-2.5" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; color: white;">
                    Submit Application & Generate Token
                </button>
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
                inmateSection.querySelectorAll('input, select').forEach(el => { 
                    el.removeAttribute('required'); 
                    el.value = ''; 
                });
                submitBtn.disabled = false;
                banStatus.classList.add('d-none');
            } else {
                inmateSection.style.display = 'block';
                inmateSection.querySelectorAll('.target-field, #inmateCid').forEach(el => {
                    el.setAttribute('required', 'true');
                });
            }
        });

        inmateCid.addEventListener('blur', async function() {
            if(!this.value || typeSelect.value === 'Others') return;
            try {
                const res = await fetch('check_ban.php?cid=' + this.value);
                const data = await res.json();
                if(data.banned) {
                    banStatus.textContent = "⚠️ ACCESS DENIED: This inmate's privileges are suspended.";
                    banStatus.classList.remove('d-none');
                    submitBtn.disabled = true;
                } else {
                    banStatus.classList.add('d-none');
                    submitBtn.disabled = false;
                }
            } catch(e) { 
                console.error(e); 
            }
        });
    </script>
<?php endif; ?>
</div>
</body>
</html>
