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

    // Process and validate Accompanying Visitors Array
    $accNames = $_POST['accName'] ?? [];
    $accCids = $_POST['accCid'] ?? [];
    $accRelations = $_POST['accRelation'] ?? [];
    
    $accompanyingList = [];
    for ($i = 0; $i < count($accNames); $i++) {
        if (!empty(trim($accNames[$i]))) {
            $accompanyingList[] = [
                'name' => $accNames[$i],
                'cid' => $accCids[$i] ?? '',
                'relation' => $accRelations[$i] ?? ''
            ];
        }
    }
    
    // Safety check filter: Reject registration if entries exceed 6 rows
    if (count($accompanyingList) > 6) {
        $errorMessage = "❌ Registration Rejected: You cannot have more than 6 accompanying visitors per application.";
    }

    if (!$errorMessage && $visitorType !== 'Others' && !empty($inmateCid)) {
        $checkBan = $db->prepare("SELECT COUNT(*) FROM banned_inmates WHERE inmateCid = ?");
        $checkBan->execute([$inmateCid]);
        if ($checkBan->fetchColumn() > 0) {
            $errorMessage = "⚠️ Registration Blocked: This inmate's privileges are suspended due to an active restriction notice.";
        }
    }

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
        $flatAccompanyingText = !empty($accompanyingList) ? json_encode($accompanyingList) : null;

        $stmt = $db->prepare("INSERT INTO visitors (inmateName, inmateCid, block, visitorName, visitorCid, relationship, visitorType, cidPhoto, accompanyingData) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$inmateName, $inmateCid, $block, $visitorName, $visitorCid, $relationship, $visitorType, $photoPath, $flatAccompanyingText]);
        
        $successData = [
            'name' => $visitorName, 
            'cid' => $visitorCid, 
            'type' => $visitorType,
            'photo' => $photoPath,
            'count' => count($accompanyingList)
        ];
    }
}
?>

<?php include 'header.php'; ?>

<style>
    .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    
    /* Centered Image Preview Frame Container */
    .image-preview-frame { 
        background: #ffffff; 
        border: 2px dashed #6366f1; 
        border-radius: 20px; 
        padding: 1rem; 
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.1); 
        display: block; 
        margin: 1.5rem auto !important; /* Force browser automatic margin centering allocation */
        max-width: 260px;
        width: 100%;
        text-align: center;
    }
    
    .horizontal-field-row { display: flex; align-items: center; margin-bottom: 1.25rem; }
    .acc-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 0.75rem; position: relative; }
    
    @media (max-width: 768px) {
        .horizontal-field-row { flex-direction: column; align-items: flex-start; }
        .horizontal-field-row label { margin-bottom: 0.25rem !important; width: 100% !important; }
        .horizontal-field-row div { width: 100% !important; }
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container py-4">
<?php if ($successData): ?>
    <!-- 📄 Digital Receipt Pass Layout View -->
    <div class="beautiful-card mx-auto my-3 animate-fade-in" style="max-width: 520px;">
        <div class="card-header-gradient text-center py-4">
            <h4 class="m-0 fw-bold" style="color: white;">✨ Access Token Generated</h4>
        </div>
        
        <!-- Force Full Center Container alignment parameters on all elements -->
        <div class="p-5 bg-white d-flex flex-column align-items-center justify-content-center text-center">
            
            <div class="alert alert-success d-flex align-items-center gap-2 w-100 rounded-3 mb-4 fw-semibold justify-content-center">
                ✅ Record Registered Successfully
            </div>
            
            <p class="text-secondary small mb-2 px-2">Please ask the security team at <strong>Gate 2 Checkpoint</strong> to pull up your account credentials to execute verification clearance logs.</p>
            
            <!-- Securely centered image component block -->
            <div class="image-preview-frame">
                <img src="<?php echo htmlspecialchars($successData['photo']); ?>" class="img-fluid rounded-3 mx-auto" style="max-height: 220px; width: auto; object-fit: contain; display: block;" alt="Uploaded CID Photo Verification Document">
            </div>
            
            <h3 class="fw-bold mb-1 text-dark mt-2"><?php echo htmlspecialchars($successData['name']); ?></h3>
            
            <div class="d-flex gap-2 justify-content-center align-items-center mb-3">
                <span class="badge bg-light text-secondary border px-2 py-1.5 small font-monospace">CID: <?php echo htmlspecialchars($successData['cid']); ?></span>
                <span class="badge bg-primary px-2 py-1.5 text-white small" style="background-color: #6366f1;"><?php echo $successData['type']; ?> Visit</span>
            </div>
            
            <?php if ($successData['count'] > 0): ?>
                <div class="mb-4">
                    <span class="badge bg-dark px-3 py-1.5 rounded-pill">👥 Accompanying Visitors Count: <?php echo $successData['count']; ?></span>
                </div>
            <?php endif; ?>

            <hr class="w-100 text-muted my-3">
            
            <!-- Securely Centered Action Button Wrapper Container -->
            <div class="w-100 d-flex justify-content-center align-items-center">
                <a href="index.php" class="btn btn-gradient px-5 py-2.5 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; border-radius: 14px; font-size: 1rem; width: 100%; max-width: 320px;">
                    🔄 Register Another Visitor
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- 📋 Entry Form Layout Container Framework Wrapper Page -->
    <div class="beautiful-card mx-auto animate-fade-in">
        <div class="card-header-gradient text-center"><h4 class="m-0 fw-bold" style="color: white;">Gate 1: Visitor Entry Registration Desk</h4></div>
        <div class="p-4 bg-white">
            <?php if ($errorMessage): ?><div class="alert alert-danger p-3 mb-4 rounded-3 small fw-semibold"><?php echo $errorMessage; ?></div><?php endif; ?>
            
            <form action="index.php" method="POST" enctype="multipart/form-data">
                
                <div class="horizontal-field-row">
                    <label class="form-label text-dark fw-bold m-0" style="width: 30%;">Visitor Classification</label>
                    <div style="width: 70%;">
                        <select name="visitorType" id="visitorType" class="form-select shadow-sm" required>
                            <option value="Personal">Personal Visit</option>
                            <option value="Official">Official Business</option>
                            <option value="Conjugal">Conjugal Visit</option>
                            <option value="Night Visitor">Night Visitor</option>
                            <option value="Others">Others (Hides Inmate)</option>
                        </select>
                    </div>
                </div>

                <div id="inmateSection" class="section-container mt-4">
                    <div class="form-section-title">Inmate Identification Parameters</div>
                    <div class="horizontal-field-row">
                        <label class="form-label text-secondary m-0" style="width: 30%;">Inmate National CID</label>
                        <div style="width: 70%;">
                            <input type="text" name="inmateCid" id="inmateCid" class="form-control shadow-sm" placeholder="Enter Inmate CID">
                            <div id="banStatus" class="alert alert-danger p-2 mt-2 json-alert small fw-bold d-none"></div>
                        </div>
                    </div>
                    <div class="horizontal-field-row">
                        <label class="form-label text-secondary m-0" style="width: 30%;">Inmate Full Name</label>
                        <div style="width: 70%;"><input type="text" name="inmateName" class="form-control target-field shadow-sm" placeholder="Enter Inmate Name"></div>
                    </div>
                    <div class="horizontal-field-row">
                        <label class="form-label text-secondary m-0" style="width: 30%;">Cell Block Location</label>
                        <div style="width: 70%;">
                            <select name="block" class="form-select target-field shadow-sm">
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
                    </div>
                    <div class="horizontal-field-row mb-0">
                        <label class="form-label text-secondary m-0" style="width: 30%;">Relationship with Inmate</label>
                        <div style="width: 70%;"><input type="text" name="relationship" class="form-control target-field shadow-sm" placeholder="e.g. Spouse, Sibling"></div>
                    </div>
                </div>

                <!-- Dynamic Component: Accompanying Passenger Roster Factory -->
                <div class="section-container mt-4">
                    <div class="form-section-title d-flex justify-content-between align-items-center">
                        <span>Accompanying Visitors Roster</span>
                        <button type="button" id="addAccBtn" class="btn btn-sm btn-outline-primary px-3 fw-bold rounded-pill">+ Add Visitor</button>
                    </div>
                    <div class="text-muted small mb-3">Maximum limit: <strong>6 accompanying passengers</strong>. Entries exceeding 6 will be blocked.</div>
                    
                    <div id="accompanyingWrapper">
                        <!-- Dynamic rows injected here by JavaScript -->
                    </div>
                </div>

                
                <div class="section-container mt-4">
                    <div class="form-section-title">Primary Visitor Identity Profile</div>
                    <div class="horizontal-field-row mb-3">
                        <label class="form-label text-secondary m-0" style="width: 30%;">Primary Full Name</label>
                        <div style="width: 70%;"><input type="text" name="visitorName" class="form-control shadow-sm" placeholder="Enter your full legal name" required></div>
                    </div>
                    <div class="horizontal-field-row mb-3">
                        <label class="form-label text-secondary m-0" style="width: 30%;">Primary National CID</label>
                        <div style="width: 70%;"><input type="text" name="visitorCid" class="form-control shadow-sm" placeholder="Enter your official card identifier numbers" required></div>
                    </div>
                    <div class="horizontal-field-row mb-0">
                        <label class="form-label text-secondary m-0" style="width: 30%;">Upload Primary CID Image</label>
                        <div style="width: 70%;"><input type="file" name="cidPhoto" class="form-control shadow-sm" accept="image/*" required></div>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-gradient w-100 py-3 mt-3 fw-bold text-white shadow" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; border-radius: 14px;">Verify Credentials &amp; Issue Pass</button>
            </form>
        </div>
    </div>

    <script>
        const typeSelect = document.getElementById('visitorType');
        const inmateSection = document.getElementById('inmateSection');
        const inmateCid = document.getElementById('inmateCid');
        const submitBtn = document.getElementById('submitBtn');
        const banStatus = document.getElementById('banStatus');
        
        const addAccBtn = document.getElementById('addAccBtn');
        const accompanyingWrapper = document.getElementById('accompanyingWrapper');

        addAccBtn.addEventListener('click', function() {
            const currentRows = accompanyingWrapper.querySelectorAll('.acc-box').length;
            if (currentRows >= 6) {
                alert("🛑 Structural Limit Enforced: You cannot add more than 6 accompanying rows.");
                return;
            }

            const div = document.createElement('div');
            div.className = 'acc-box animate-fade-in';
            div.innerHTML = `
                <div class="row g-2 mb-2">
                    <div class="col-md-4"><input type="text" name="accName[]" class="form-control form-control-sm" placeholder="Full Name" required></div>
                    <div class="col-md-4"><input type="text" name="accCid[]" class="form-control form-control-sm" placeholder="CID No." required></div>
                    <div class="col-md-4"><input type="text" name="accRelation[]" class="form-control form-control-sm" placeholder="Relation" required></div>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 position-absolute end-0 top-0 mt-1 me-2 remove-acc-btn" style="text-decoration:none;">✕ Remove</button>
            `;
            accompanyingWrapper.appendChild(div);
        });

        accompanyingWrapper.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-acc-btn')) {
                e.target.closest('.acc-box').remove();
            }
        });

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
            } catch(e) { console.error(e); }
        });
    </script>
<?php endif; ?>
</div>
</body>
</html>
