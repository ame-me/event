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

$page_title = "Backup & Restore Database";
$success_msg = "";
$error_msg = "";

// 1. Handle Backup Action (Download)
if (isset($_GET['action']) && $_GET['action'] == 'download') {
    $filename = "backup_kms_" . date('Y-m-d_H-i-s') . ".sql";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Simple backup command via mysqldump (assuming XAMPP path)
    $command = "C:\\xampp\\mysql\\bin\\mysqldump --user=root --password= event_kms";
    system($command);
    exit();
}

// 2. Handle Restore Action (Upload and Import)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'restore') {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
        $file_tmp = $_FILES['backup_file']['tmp_name'];
        $file_name = $_FILES['backup_file']['name'];
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        if (strtolower($ext) === 'sql') {
            try {
                // Read SQL file content
                $sql = file_get_contents($file_tmp);
                
                // Disable foreign key checks before restore to prevent constraint errors
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                
                // Execute SQL
                $pdo->exec($sql);
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                
                $success_msg = "Database berhasil dipulihkan dari file: " . htmlspecialchars($file_name);
            } catch (Exception $e) {
                $error_msg = "Gagal memulihkan database: " . $e->getMessage();
            }
        } else {
            $error_msg = "Format file tidak valid. Silakan unggah file dengan ekstensi .sql";
        }
    } else {
        $error_msg = "Gagal mengunggah file. Silakan coba lagi.";
    }
}
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
         .backup-container { max-width: 700px; margin: 0 auto; }
         .tab-control { display: flex; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; gap: 20px; }
         .tab-btn { padding: 10px 20px; border: none; background: none; font-size: 1rem; font-weight: 600; color: var(--text-secondary); cursor: pointer; position: relative; transition: color 0.2s; }
         .tab-btn:hover { color: var(--primary-color); }
         .tab-btn.active { color: var(--primary-color); }
         .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: var(--primary-color); }
         
         .tab-content { display: none; }
         .tab-content.active { display: block; }
         
         .upload-zone { border: 2px dashed #cbd5e1; border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center; background: #f8fafc; cursor: pointer; transition: border-color 0.2s, background-color 0.2s; }
         .upload-zone:hover { border-color: var(--primary-color); background: rgba(13, 71, 161, 0.02); }
         .upload-zone i { font-size: 3.5rem; color: var(--text-secondary); margin-bottom: 1rem; }
         .file-input { display: none; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="page-title"><?= $page_title ?></div>
                <div class="user-profile">
                    <span><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>

            <div class="content-body">
                <div class="backup-container">
                    
                    <?php if ($success_msg): ?>
                        <div class="alert badge-success" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #10b981; border-radius: var(--radius-md);">
                            <i class='bx bx-check-circle' style="font-size: 1.25rem; vertical-align: middle;"></i> <?= $success_msg ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-error" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #ef4444; border-radius: var(--radius-md);">
                            <i class='bx bx-error-circle' style="font-size: 1.25rem; vertical-align: middle;"></i> <?= $error_msg ?>
                        </div>
                    <?php endif; ?>

                    <div class="tab-control">
                        <button class="tab-btn active" data-tab="tabBackup">Backup Database</button>
                        <button class="tab-btn" data-tab="tabRestore">Restore Database</button>
                    </div>

                    <!-- TAB BACKUP -->
                    <div id="tabBackup" class="tab-content active">
                        <div class="card" style="text-align: center; padding: 3rem 2rem;">
                            <i class='bx bx-cloud-upload' style="font-size: 5rem; color: var(--primary-color); margin-bottom: 1.5rem; display: block;"></i>
                            <h2 style="margin-bottom: 0.5rem; color: var(--text-primary);">Cadangkan Data Sistem</h2>
                            <p style="color: var(--text-secondary); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6;">
                                Unduh salinan database sistem saat ini dalam format file `.sql`. 
                                Cadangan ini berisi seluruh data kegiatan, repository dokumen, lesson learned, evaluasi, dan akun pengguna.
                            </p>
                            <a href="backup.php?action=download" class="btn btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: auto; padding: 12px 24px;">
                                <i class='bx bx-download' style="font-size: 1.25rem;"></i> Unduh Cadangan Database
                            </a>
                        </div>
                    </div>

                    <!-- TAB RESTORE -->
                    <div id="tabRestore" class="tab-content">
                        <div class="card" style="padding: 2.5rem 2rem;">
                            <h2 style="margin-bottom: 0.5rem; color: var(--text-primary);">Pulihkan Data Sistem</h2>
                            <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6;">
                                Unggah file cadangan `.sql` yang sebelumnya diunduh untuk memulihkan seluruh database ke kondisi semula. 
                                <strong style="color: #ef4444;">Peringatan:</strong> Proses ini akan menimpa seluruh data saat ini dengan data dari file cadangan.
                            </p>
                            
                            <form action="backup.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="restore">
                                
                                <div class="upload-zone" id="uploadZone">
                                    <i class='bx bx-cloud-upload'></i>
                                    <p id="fileInfo" style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;">
                                        Klik untuk memilih file `.sql` cadangan Anda di sini untuk memulihkan database.
                                    </p>
                                    <input type="file" name="backup_file" id="fileInput" class="file-input" accept=".sql" required>
                                </div>
                                
                                <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                                    <button type="submit" class="btn btn-primary" style="width: auto; padding: 12px 30px;">
                                        <i class='bx bx-refresh' style="font-size: 1.25rem; vertical-align: middle; margin-right: 5px;"></i> Mulai Pemulihan (Restore)
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab switching logic
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                btn.classList.add('active');
                const target = btn.getAttribute('data-tab');
                document.getElementById(target).classList.add('active');
            });
        });

        // File upload zone interactive logic
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');

        uploadZone.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                fileInfo.innerHTML = `<strong>File terpilih:</strong> ${fileInput.files[0].name} (${(fileInput.files[0].size / 1024).toFixed(2)} KB)`;
                fileInfo.style.color = 'var(--primary-color)';
            } else {
                fileInfo.innerHTML = 'Klik untuk memilih file `.sql` cadangan Anda di sini untuk memulihkan database.';
                fileInfo.style.color = 'var(--text-secondary)';
            }
        });
    </script>
</body>
</html>
