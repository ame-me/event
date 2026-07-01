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

$page_title = "Standarisasi & Template Dokumen";

// Fetch unique template types for dropdown option list
$default_types = ["Proposal", "Laporan", "SOP", "Rundown"];
$db_types_stmt = $pdo->query("SELECT DISTINCT tipe FROM templates WHERE tipe != ''");
$db_types = $db_types_stmt->fetchAll(PDO::FETCH_COLUMN);
$all_types = array_unique(array_merge($default_types, $db_types));
sort($all_types);

// Handle upload for templates (Only Admin can upload)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_template' && $_SESSION['role_id'] == 1) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $nama_template = $_POST['nama_template'];
        $tipe = $_POST['tipe'];
        if ($tipe === '__BARU__' && !empty($_POST['tipe_baru'])) {
            $tipe = trim($_POST['tipe_baru']);
        }
        
        $upload_dir = 'uploads/templates/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        
        $file_info = pathinfo($_FILES['file']['name']);
        $new_filename = 'TEMPLATE_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $tipe) . '_' . time() . '.' . $file_info['extension'];
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
            $insert = $pdo->prepare("INSERT INTO templates (nama_template, tipe, file_path) VALUES (?, ?, ?)");
            $insert->execute([$nama_template, $tipe, $destination]);
            header("Location: template.php?msg=success");
            exit();
        }
    }
}

// Fetch templates
$stmt = $pdo->query("SELECT * FROM templates ORDER BY id DESC");
$templates = $stmt->fetchAll();


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - KMS SMA Santa Maria</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
         .tpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
         .tpl-card { border: 1px solid var(--border-color); border-radius: var(--radius-lg); background: white; padding: 1.5rem; text-align: center; transition: 0.2s; box-shadow: var(--shadow-sm); }
         .tpl-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--primary-color); }
         .tpl-icon { font-size: 3rem; margin-bottom: 1rem; color: var(--text-secondary); }
         .tpl-title { font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem; font-size: 1.05rem; }
         .tpl-badge { font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; background: rgba(13, 71, 161, 0.1); color: var(--primary-color); display: inline-block; margin-bottom: 1rem; }
         
         .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
         .modal.active { display: flex; }
         .modal-content { background-color: var(--surface-color); padding: 2rem; border-radius: var(--radius-lg); width: 100%; max-width: 500px; position: relative; }
         .close-btn { position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div class="page-title"><?= $page_title ?></div>
                <div class="user-profile">
                    <span style="font-weight: 500;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>
            
            <div class="content-body">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h2 style="font-size: 1.5rem; color: var(--text-primary);">Download Template Standar</h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Gunakan format yang seragam untuk semua dokumen kepanitiaan.</p>
                    </div>
                    <?php if($_SESSION['role_id'] == 1): ?>
                    <button class="btn btn-primary" onclick="document.getElementById('modalUpload').classList.add('active')" style="width: auto;">
                        <i class='bx bx-upload'></i> Upload Template (Admin)
                    </button>
                    <?php endif; ?>
                </div>

                <div class="tpl-grid">
                    <?php foreach($templates as $tpl): ?>
                        <div class="tpl-card">
                            <?php 
                                $icon = 'bxs-file-doc'; $color = '#3b82f6';
                                if($tpl['tipe'] == 'Proposal') { $icon = 'bx-buildings'; $color = '#8b5cf6'; }
                                if($tpl['tipe'] == 'SOP') { $icon = 'bx-sitemap'; $color = '#10b981'; }
                                if($tpl['tipe'] == 'Rundown') { $icon = 'bxs-spreadsheet'; $color = '#16a34a'; }
                                if($tpl['tipe'] == 'Laporan') { $icon = 'bx-bar-chart-alt'; $color = '#f59e0b'; }
                            ?>
                            <i class='bx <?= $icon ?> tpl-icon' style="color: <?= $color ?>;"></i>
                            <h3 class="tpl-title"><?= htmlspecialchars($tpl['nama_template']) ?></h3>
                            <span class="tpl-badge"><?= htmlspecialchars($tpl['tipe']) ?></span>
                            <div style="margin-top: 10px;">
                                <a href="<?= htmlspecialchars($tpl['file_path']) ?>" download class="btn" style="background: rgba(13, 71, 161, 0.1); color: var(--primary-color); display: inline-block; width: 100%; border: 1px solid rgba(13, 71, 161, 0.2);">
                                    <i class='bx bx-cloud-download'></i> Download Template
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form (Admin Only) -->
    <?php if($_SESSION['role_id'] == 1): ?>
    <div id="modalUpload" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('modalUpload').classList.remove('active')">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Update Template Baru</h2>
            <form action="template.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_template">
                <div class="form-group">
                    <label>Nama Template</label>
                    <input type="text" name="nama_template" class="form-control" required placeholder="Contoh: Format Resmi Proposal 2024">
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="tipe" class="form-control" required style="appearance: auto; background-color: #fff;">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($all_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                        <option value="__BARU__">+ Kategori Baru...</option>
                    </select>
                    <div id="tipe_baru_wrapper" style="display: none; margin-top: 10px;">
                        <label style="font-weight: 500; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nama Kategori Baru</label>
                        <input type="text" name="tipe_baru" id="tipe_baru" class="form-control" placeholder="Masukkan kategori baru">
                    </div>
                </div>
                <div class="form-group">
                    <label>File (DOCX/XLSX)</label>
                    <input type="file" name="file" class="form-control" required style="padding: 0.5rem;" accept=".doc,.docx,.xls,.xlsx">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Upload Template</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
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
