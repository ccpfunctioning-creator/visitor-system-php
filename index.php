<?php
// Initialize session cleanly at the absolute top of the stack
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

    // Backend validation against banned inmates collection
    if ($visitorType !== 'Others' && !empty($inmateCid)) {
        $checkBan = $db->prepare("SELECT COUNT(*) FROM banned_inmates WHERE inmateCid = ?");
        $checkBan->execute([$inmateCid]);
        if ($checkBan->fetchColumn() > 0) {
            $errorMessage = "⚠️ Registration Blocked: This inmate's privileges are suspended due to an active restriction notice.";
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
            $errorMessage = "⚠️ Failed to process and upload your identification document snapshot.";
        }
    }

    if (!$errorMessage) {
        $stmt = $db->prepare("INSERT INTO visitors (inmateName, inmateCid, block, visitorName, visitorCid, relationship, visitorType, cidPhoto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$inmateName, $inmateCid, $block, $visitorName, $visitorCid, $relationship, $visitorType, $photoPath]);
        
        $lastId = $db->lastInsertId();
        
        // 🚀 BULLETPROOF DOCKER & RENDER PUBLIC URL AUTO-DETECTION
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        
        // If Docker inside Render outputs internal network IDs, fallback to your public app name cleanly
        if (strpos($host, 'render.local') !== false || $host === 'localhost' || $host === '127.0.0.1') {
            $host = '://onrender.com'; 
        }
        
        $verificationUrl = $protocol . $host . "/gate2.php?searchId=" . $lastId;
        
        // Premium, clean high-resolution QR configuration parameters link
        $qrImage = "https://qrserver.com" . urlencode($verificationUrl);
        
        $successData = [
            'qr' => $qrImage, 
            'name' => $visitorName, 
            'cid' => $visitorCid, 
            'type' => $visitorType,
            'url' => $verificationUrl
        ];
    }
}
?>

<?php include 'header.php'; ?>

<style>
    /* Professional Layout Enhancements */
    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }
    .qr-frame {
        background: #ffffff;
        border: 2px dashed #cbd5e1;
        border-radius: 20px;
        padding: 1.25rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }
    .qr-frame:hover {
        transform: scale(1.02);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="container py-4">
<?php if ($successData): ?>
    <!-- 📄 Ultra-Professional Digital Token Pass Layout View -->
    <div class="beautiful-card mx-auto my-3 text-center animate-fade-in" style="max-width: 500px;">
        <div class="card-header-gradient py-4">
            <h4 class="m-0 fw-bold">✨ Access Pass Token Issued</h4>
        </div>
        <div class="p-5 bg-white d-flex flex-column align-items-center">
            <div class="alert alert-success d-flex align-items-center gap-2 w-100 rounded-3 mb-4 fw-semibold justify-content-center">
                ✅ Record Registered Successfully
            </div>
            
            <p class="text-secondary small mb-4">Please save or present this digital token receipt directly to the officer on desk duty at Gate 2 checkpoints.</p>
            
            <!-- Secure QR Container Framework Block -->
            <div class="qr-frame mb-4">
                <img src="<?php echo $successData['qr']; ?>" 
                     style="width: 210px; height: 210px; display: block;" 
                     alt="Verification Pass QR Token"
                     onerror="this.onerror=null; this.src='https://qrserver.com<?php echo urlencode($successData['url']); ?>';">
            </div>
            
            <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($successData['name']); ?></h4>
            <div class="d-flex gap-2 justify-content-center align-items-center mb-3">
                <span class="badge bg-light text-secondary border px-2 py-1.5 small font-monospace">CID: <?php echo htmlspecialchars($successData['cid']); ?></span>
                <span class="badge bg-purple px-2 py-1.5 text-white small" style="background-color: #6366f1;"><?php echo $successData['type']; ?> Segment</span>
            </div>
            
            <hr class="w-100 text-muted my-3">
            
            <a href="index.php" class="btn btn-gradient w-100 py-2.5 mt-1 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                Register Another Visitor
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- 📋 Entry Form Layout Container Framework Wrapper Page -->
    <div class="beautiful-card mx-auto animate-fade-in">
        <div class="card-header-gradient">
            <h4 class="m-0 fw-bold">Gate 1: Visitor Entry Registration Desk</h4>
        </div>
        <div class="p-4 bg-white">
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger p-3 mb-4 rounded-3 small fw-semibold"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label text-dark fw-bold">Visitor Classification Category</label>
                    <select name="visitorType" id="visitorType" class="form-select shadow-sm" required>
                        <option value="Personal">👪 Personal Visit</option>
                        <option value="Official">💼 Official Business</option>
                        <option value="Conjugal">💍 Conjugal Visit</option>
                        <option value="Night Visitor">🌙 Night Visitor</option>
                        <option value="Others">⚙️ Others (Collapses Inmate Fields)</option>
                    </select>
                </div>

                <!-- Block Section: Target Inmate Data Elements -->
                <div id="inmateSection" class="section-container">
                    <div class="form-section-title">Inmate Identification Parameters</div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Inmate National CID No.</label>
                        <input type="text" name="inmateCid" id="inmateCid" class="form-control" placeholder="Enter Inmate Identification Account Number">
                        <div id="banStatus" class="alert alert-danger p-2 mt-2 small fw-bold d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Inmate Full Name</label>
                        <input type="text" name="inmateName" class="form-control target-field" placeholder="Enter Inmate Legal First & Last Name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Cell Block Assignment Location</label>
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
                        <label class="form-label text-secondary">Relationship with Inmate</label>
                        <input type="text" name="relationship" class="form-control target-field" placeholder="e.g. Spouse, Sibling, Legal Representative">
                    </div>
                </div>

                <!-- Block Section: Submitting Visitor Data Elements -->
                <div class="section-container">
                    <div class="form-section-title">Visitor Identification Profile</div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Visitor Full Name</label>
                        <input type="text" name="visitorName" class="form-control" placeholder="Enter your full legal name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Visitor National CID Card Number</label>
                        <input type="text" name="visitorCid" class="form-control" placeholder="Enter your official card identifier numbers" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-secondary">Upload Official CID Photo Identification Document</label>
                        <input type="file" name="cidPhoto" class="form-control" accept="image/*" required>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-gradient w-100 py-3 mt-2 fw-bold text-white shadow" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; font-size: 1.05rem; border-radius: 14px;">
                    Verify Credentials & Issue Pass Ticket Token
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
                    banStatus.textContent = "⚠️ ACCESS DENIED: This inmate's privileges are currently suspended due to active administrative restrictions.";
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
