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
        
        // Push the database update back to GitHub
        backupDatabaseToGitHub();

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
    
    .image-preview-frame { 
        background: #ffffff; 
        border: 2px dashed #6366f1; 
        border-radius: 20px; 
        padding: 0.75rem; 
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.1); 
        display: block; 
        margin: 1.5rem auto !important;
        max-width: 100%;
        width: 260px;
        text-align: center;
    }
    
    .acc-box { 
        background: #f8fafc; 
        border: 1px solid #e2e8f0; 
        border-radius: 14px; 
        padding: 1.25rem; 
        margin-bottom: 1rem; 
        position: relative; 
    }

    .form-card-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        padding: 2rem;
    }

    .custom-label-style {
        font-weight: 600;
        color: #334155;
        font-size: 0.9rem;
        margin-bottom: 0.4rem;
    }

    .custom-input-style {
        border-radius: 10px !important;
        padding: 0.6rem 1rem;
        border: 1px solid #cbd5e1;
        transition: all 0.2s ease;
    }

    .custom-input-style:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .section-divider-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #64748b;
        font-weight: 700;
        margin: 2rem 0 1.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-divider-title::after {
        content: '';
        flex-grow: 1;
        height: 1px;
        background: #e2e8f0;
    }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container py-3 py-md-5">
<?php if ($successData): ?>
    <!-- 📄 Centered Digital Receipt Pass Layout View -->
    <div class="beautiful-card mx-auto my-2 animate-fade-in shadow-lg" style="max-width: 520px;">
        <div class="card-header-gradient text-center py-4">
            <h4 class="m-0 fw-bold" style="color: white;">✨ Access Token Generated</h4>
        </div>
        
        <div class="p-4 p-md-5 bg-white d-flex flex-column align-items-center justify-content-center text-center">
            <div class="alert alert-success d-flex align-items-center gap-2 w-100 rounded-3 mb-4 fw-semibold justify-content-center small">
                ✅ Record Registered &amp; Cloud Synced Successfully
            </div>
            
            <p class="text-secondary small mb-2 px-1">Please ask the security team at <strong>Gate 2 Checkpoint</strong> to pull up your credentials to execute verification logs.</p>
            
            <div class="image-preview-frame">
                <img src="<?php echo htmlspecialchars($successData['photo']); ?>" class="img-fluid rounded-3 mx-auto" style="max-height: 220px; width: auto; object-fit: contain; display: block;" alt="Uploaded Photo">
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

            <hr class="w-100 text-muted my-2">
            
            <div class="w-100">
                <a href="index.php" class="btn btn-gradient py-2.5 fw-bold text-white shadow w-100" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; border-radius: 12px; font-size: 0.95rem;">
                    🔄 Register Another Visitor
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- 📋 Fully Responsive Grid Form Container -->
    <div class="form-card-container mx-auto animate-fade-in" style="max-width: 850px;">
        <form action="index.php" method="POST" enctype="multipart/form-data">
            
            <!-- Classification Row -->
            <div class="row mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label custom-label-style">Visitor Classification</label>
                    <select name="visitorType" id="visitorType" class="form-select custom-input-style" required>
                        <option value="Personal">👪 Personal Visit</option>
                        <option value="Official">💼 Official Business</option>
                        <option value="Conjugal">💍 Conjugal Visit</option>
                        <option value="Night Visitor">🌙 Night Visitor</option>
                        <option value="Others">⚙️ Others (Hides Inmate Fields)</option>
                    </select>
                </div>
            </div>

            <!-- Section Header: Inmate Parameters -->
            <div id="inmateSection">
                <div class="section-divider-title">Inmate Identification Parameters</div>
                
                <!-- Dual-Column Balanced Grid Row Box -->
                <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                    <div>
                        <label class="form-label custom-label-style">Inmate National CID</label>
                        <input type="text" name="inmateCid" id="inmateCid" class="form-control custom-input-style" placeholder="Enter Inmate CID Number">
                        <div id="banStatus" class="alert alert-danger p-2 mt-2 json-alert small fw-bold d-none"></div>
                    </div>
                    <div>
                        <label class="form-label custom-label-style">Cell Block Location</label>
                        <select name="block" class="form-select custom-input-style">
                            <option value="Block I">Block I</option><option value="Block II">Block II</option><option value="Block III">Block III</option>
                            <option value="Block IV">Block IV</option><option value="Block V">Block V</option><option value="Block VI">Block VI</option>
                            <option value="Block VII">Block VII</option><option value="Block VIII">Block VIII</option><option value="Block IX">Block IX</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label custom-label-style">Inmate Full Name</label>
                        <input type="text" name="inmateName" class="form-control target-field custom-input-style" placeholder="Enter Inmate Legal Full Name">
                    </div>
                    <div>
                        <label class="form-label custom-label-style">Relationship with Inmate</label>
                        <input type="text" name="relationship" class="form-control target-field custom-input-style" placeholder="e.g., Spouse, Sibling, Parent">
                    </div>
                </div>
            </div>

            <!-- Accompanying Visitors Section -->
            <div class="section-divider-title d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Accompanying Visitors Roster</span>
                <button type="button" id="addAccBtn" class="btn btn-sm btn-outline-primary px-3 fw-bold rounded-pill">+ Add Visitor</button>
            </div>
            <div class="text-muted small mb-3">Maximum limit: <strong>6 accompanying passengers</strong>. Entries exceeding 6 will be blocked.</div>
            
            <div id="accompanyingWrapper">
                <!-- Dynamic rows injected here by JavaScript -->
            </div>

            <!-- Primary Visitor Profile Section -->
            <div class="section-divider-title">Primary Visitor Identity Profile</div>
            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div>
                    <label class="form-label custom-label-style">Primary Full Name</label>
                    <input type="text" name="visitorName" class="form-control custom-input-style" placeholder="Enter your full legal name" required>
                </div>
                <div>
                    <label class="form-label custom-label-style">Primary National CID</label>
                    <input type="text" name="visitorCid" class="form-control custom-input-style" placeholder="Enter your card number" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label custom-label-style">Upload Primary CID Image</label>
                    <input type="file" name="cidPhoto" class="form-control custom-input-style" accept="image/*" required>
                </div>
            </div>

            <!-- Action Controls Styled into the Bottom-Right Corner -->
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="index.php" class="btn btn-light px-4 py-2 fw-semibold border rounded-3" style="color: #475569;">Reset</a>
                <button type="submit" id="submitBtn" class="btn text-white px-4 py-2 fw-bold rounded-3 shadow d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                    Verify &amp; Issue Pass
                </button>
            </div>
        </form>
    </div>

    <script>
        const typeSelect = document.getElementById('visitorType');
        const inmateSection = document.getElementById('inmateSection');
        const inmateCid = document.getElementById('inmateCid');
        const submitBtn = document.getElementById('submitBtn');
        const banStatus = document.getElementById('banStatus');
        
        const addAccBtn = document.getElementById('addAccBtn');
        const accompanyingWrapper = document.getElementById('accompanyingWrapper');

        // Dynamic Rows Controller Logic
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
                    <div class="col-12 col-md-4"><input type="text" name="accName[]" class="form-control form-control-sm py-1.5" placeholder="Full Name" required></div>
                    <div class="col-12 col-md-4"><input type="text" name="accCid[]" class="form-control form-control-sm py-1.5" placeholder="CID No." required></div>
                    <div class="col-12 col-md-4"><input type="text" name="accRelation[]" class="form-control form-control-sm py-1.5" placeholder="Relation" required></div>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 position-absolute end-0 top-0 mt-1 me-2 remove-acc-btn" style="text-decoration:none; font-size:0.8rem;">✕ Remove</button>
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
