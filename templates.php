<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!has_permission(basename($_SERVER['PHP_SELF']))) {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

$page_title = "Template & Standarisasi Dokumen";
$role_id = $_SESSION['role_id'];

// Fetch Templates
$stmt = $pdo->query("SELECT * FROM templates ORDER BY tipe ASC");
$templates = $stmt->fetchAll();

// Fetch unique template types for dropdown option list
$default_types = ["Proposal", "Laporan", "SOP", "Rundown"];
$db_types_stmt = $pdo->query("SELECT DISTINCT tipe FROM templates WHERE tipe != ''");
$db_types = $db_types_stmt->fetchAll(PDO::FETCH_COLUMN);
$all_types = array_unique(array_merge($default_types, $db_types));
sort($all_types);

// Handle Upload (Hanya Admin IT atau Manajemen yang bisa nambah template)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $role_id <= 2) {
    $nama = $_POST['nama_template'];
    $tipe = $_POST['tipe'];
    if ($tipe === '__BARU__' && !empty($_POST['tipe_baru'])) {
        $tipe = trim($_POST['tipe_baru']);
    }
    
    // File upload logic
    $target_dir = "uploads/templates/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_path = "";
    if (isset($_FILES['file_template']) && $_FILES['file_template']['error'] == 0) {
        $filename = time() . '_' . $_FILES['file_template']['name'];
        if (move_uploaded_file($_FILES['file_template']['tmp_name'], $target_dir . $filename)) {
            $file_path = $target_dir . $filename;
            
            $insert = $pdo->prepare("INSERT INTO templates (nama_template, tipe, file_path) VALUES (?, ?, ?)");
            $insert->execute([$nama, $tipe, $file_path]);
            header("Location: templates.php?msg=success");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - KMS Sanmar</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .template-card { background: white; border-radius: var(--radius-lg); padding: 1.5rem; border: 1px solid var(--border-color); transition: transform 0.2s, box-shadow 0.2s; position: relative; }
        .template-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .type-tag { position: absolute; top: 1rem; right: 1rem; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; background: #f1f5f9; color: #475569; }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; background: rgba(13, 71, 161, 0.1); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
                <div class="page-title"><?= $page_title ?></div>
                <div class="user-profile"><span><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span></div>
            </header>

            <div class="content-body">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="color: var(--text-primary);">Standar Dokumen Sekolah</h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Gunakan template ini untuk menjaga konsistensi administrasi kegiatan.</p>
                    </div>
                    <?php if($role_id <= 2): ?>
                        <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='flex'" style="width: auto;">+ Upload Template</button>
                    <?php endif; ?>
                </div>

                <div class="template-grid">
                    <?php foreach($templates as $t): ?>
                    <div class="template-card">
                        <span class="type-tag"><?= htmlspecialchars($t['tipe']) ?></span>
                        <div class="icon-box"><i class='bx bxs-file-blank'></i></div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($t['nama_template']) ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">Terakhir diperbarui: <?= date('d M Y', strtotime($t['created_at'])) ?></p>
                        <a href="<?= $t['file_path'] ?>" class="btn btn-primary" style="width: 100%; text-decoration: none; text-align: center;" download>
                            <i class='bx bx-download'></i> Download Template
                        </a>
                    </div>
                    <?php endforeach; ?>

                    <?php if(count($templates) == 0): ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-secondary);">Belum ada template dokumen yang tersedia.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Add Template -->
    <div id="modalAdd" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
        <div style="background:white; padding:2rem; border-radius:12px; width:100%; max-width:450px;">
            <h2 style="margin-bottom:1.5rem;">Upload Template Baru</h2>
            <form action="templates.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Nama Template</label>
                    <input type="text" name="nama_template" class="form-control" required placeholder="Contoh: Template Proposal Pensi">
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Tipe Dokumen</label>
                    <select name="tipe" class="form-control" required style="appearance: auto; background-color: #fff;">
                        <option value="">-- Pilih Tipe --</option>
                        <?php foreach ($all_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                        <option value="__BARU__">+ Tipe Baru...</option>
                    </select>
                    <div id="tipe_baru_wrapper" style="display: none; margin-top: 10px;">
                        <label style="font-weight: 500; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nama Tipe Baru</label>
                        <input type="text" name="tipe_baru" id="tipe_baru" class="form-control" placeholder="Masukkan tipe baru">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Pilih File</label>
                    <input type="file" name="file_template" class="form-control" required>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn" style="background:#e2e8f0;" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Toggle input for new template type
        document.querySelector('select[name="tipe"]').addEventListener('change', function() {
            var wrapper = document.getElementById('tipe_baru_wrapper');
            var input = document.getElementById('tipe_baru');
            if (this.value === '__BARU__') {
                wrapper.style.display = 'block';
                input.setAttribute('required', 'required');
                input.focus();
            } else {
                wrapper.style.display = 'none';
                input.removeAttribute('required');
            }
        });
    </script>
</body>
</html>
