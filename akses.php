<?php
session_start();
require_once 'config/database.php';

// Only Admin can access this page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!has_permission(basename($_SERVER['PHP_SELF']))) {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

$page_title = "Hak Akses Matriks";

// Fetch roles
$roles_stmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
$roles = $roles_stmt->fetchAll();

// List of controlled pages
$pages_control = [
    'pencarian.php' => 'Pencarian Terpusat',
    'kegiatan.php' => 'Manajemen Kegiatan',
    'knowledge.php' => 'Repository Dokumen',
    'lesson_learned.php' => 'Lesson Learned (Pengalaman)',
    'templates.php' => 'Template Dokumen',
    'evaluasi.php' => 'Input/Lihat Evaluasi',
    'laporan.php' => 'Laporan & Insight',
    'forum.php' => 'Forum Diskusi',
    'users.php' => 'Manajemen Pengguna',
    'backup.php' => 'Backup Data'
];

$success_msg = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_matrix') {
    try {
        $pdo->beginTransaction();
        
        // Reset all permissions to 0 first
        $pdo->query("UPDATE permissions_matrix SET is_allowed = 0");
        
        // Update permissions that are checked
        if (isset($_POST['perm'])) {
            $stmt = $pdo->prepare("INSERT INTO permissions_matrix (role_id, page_name, is_allowed) 
                                   VALUES (?, ?, 1) 
                                   ON DUPLICATE KEY UPDATE is_allowed = 1");
            foreach ($_POST['perm'] as $r_id => $pages) {
                foreach ($pages as $p_name => $val) {
                    $stmt->execute([$r_id, $p_name]);
                }
            }
        }
        
        $pdo->commit();
        $success_msg = "Matriks hak akses berhasil diperbarui!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Gagal memperbarui hak akses: " . $e->getMessage();
    }
}

// Fetch current permissions matrix
$matrix_stmt = $pdo->query("SELECT * FROM permissions_matrix");
$matrix_data = [];
while ($row = $matrix_stmt->fetch()) {
    $matrix_data[$row['role_id']][$row['page_name']] = $row['is_allowed'];
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
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .matrix-table th, .matrix-table td {
            text-align: center;
            vertical-align: middle;
            padding: 12px;
        }
        .matrix-table th:first-child, .matrix-table td:first-child {
            text-align: left;
            font-weight: 500;
        }
        .checkbox-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
        }
        .checkbox-container input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }
        .sticky-col {
            position: sticky;
            left: 0;
            background: var(--surface-color);
            z-index: 5;
            box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1);
        }
        .legend-card {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
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
                    <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>

            <div class="content-body">
                <?php if ($success_msg): ?>
                    <div class="alert badge-success" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #10b981; border-radius: var(--radius-md);">
                        <i class='bx bx-check-circle' style="font-size: 1.2rem; vertical-align: middle;"></i> <?= htmlspecialchars($success_msg) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-error" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #ef4444; border-radius: var(--radius-md);">
                        <i class='bx bx-x-circle' style="font-size: 1.2rem; vertical-align: middle;"></i> <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <div class="legend-card">
                    <h3 style="margin-bottom: 0.5rem; color: var(--primary-color);"><i class='bx bx-info-circle'></i> Petunjuk Matriks Hak Akses</h3>
                    <p style="color: var(--text-secondary); line-height: 1.5;">
                        Centang kotak pada persimpangan fitur dan aktor untuk memberikan izin akses halaman tersebut kepada peran yang bersangkutan. 
                        Perubahan akan langsung berdampak pada menu di navigasi sidebar dan memblokir akses langsung URL ke halaman tersebut bagi peran yang tidak dicentang.
                    </p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Matriks Otorisasi Pengguna</h2>
                    </div>
                    
                    <form action="akses.php" method="POST">
                        <input type="hidden" name="action" value="update_matrix">
                        
                        <div class="table-responsive">
                            <table class="matrix-table">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e2e8f0;">
                                        <th class="sticky-col" style="text-align: left; width: 30%;">Fitur / Modul</th>
                                        <?php foreach ($roles as $role): ?>
                                            <th><?= htmlspecialchars($role['role_name']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pages_control as $page_file => $page_title_id): ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td class="sticky-col">
                                            <strong><?= htmlspecialchars($page_title_id) ?></strong><br>
                                            <small style="color: var(--text-secondary); font-family: monospace;"><?= htmlspecialchars($page_file) ?></small>
                                        </td>
                                        <?php foreach ($roles as $role): 
                                            $role_id = $role['id'];
                                            $checked = (isset($matrix_data[$role_id][$page_file]) && $matrix_data[$role_id][$page_file] == 1) ? 'checked' : '';
                                        ?>
                                        <td>
                                            <div class="checkbox-container">
                                                <input type="checkbox" name="perm[<?= $role_id ?>][<?= htmlspecialchars($page_file) ?>]" value="1" <?= $checked ?>>
                                            </div>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 10px;">
                            <a href="users.php" class="btn" style="background: #e2e8f0; color: #1e293b; width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Batal</a>
                            <button type="submit" class="btn btn-primary" style="width: auto; padding: 10px 25px;"><i class='bx bx-save'></i> Simpan Perubahan Matriks</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
