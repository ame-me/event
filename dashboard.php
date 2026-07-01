<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role_id = $_SESSION['role_id'];

if ($role_id == 4) {
    // Stats khusus Yayasan (Decision Maker)
    $stats_kegiatan = $pdo->query("SELECT COUNT(*) FROM kegiatan")->fetchColumn();
    $stats_docs = $pdo->query("SELECT COUNT(*) FROM knowledge_docs")->fetchColumn();
    $stats_bp = $pdo->query("SELECT COUNT(*) FROM lesson_learned WHERE is_best_practice = 1")->fetchColumn();
    $stats_rating = $pdo->query("SELECT AVG(skor_rating) FROM evaluasi")->fetchColumn();
    
    // Highlight Best Practices for Yayasan
    $recent_bp = $pdo->query("SELECT ll.*, k.nama_kegiatan FROM lesson_learned ll 
                             JOIN kegiatan k ON ll.kegiatan_id = k.id 
                             WHERE is_best_practice = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
    
    // Evaluasi Terbaru for Yayasan
    $recent_eval = $pdo->query("SELECT e.*, k.nama_kegiatan FROM evaluasi e 
                               JOIN kegiatan k ON e.kegiatan_id = k.id 
                               ORDER BY e.created_at DESC LIMIT 5")->fetchAll();
} else {
    // Stats untuk Manajemen/Panitia (Operasional)
    $stats_kegiatan = $pdo->query("SELECT COUNT(*) FROM kegiatan")->fetchColumn();
    $stats_docs = $pdo->query("SELECT COUNT(*) FROM knowledge_docs")->fetchColumn();
    $stats_bp = $pdo->query("SELECT COUNT(*) FROM lesson_learned WHERE is_best_practice = 1")->fetchColumn();
}

// Fetch latest activities
$stmt_kegiatan = $pdo->query("SELECT * FROM kegiatan ORDER BY created_at DESC LIMIT 5");
$kegiatan_berjalan = $stmt_kegiatan->fetchAll();

// Fetch latest knowledge
$stmt_knowledge = $pdo->query("SELECT kd.*, u.nama_lengkap FROM knowledge_docs kd JOIN users u ON kd.uploader_id = u.id ORDER BY kd.upload_date DESC LIMIT 3");
$knowledge_list = $stmt_knowledge->fetchAll();

$page_title = "Beranda Dashboard";
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
        .role-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; margin-left: 10px; }
        .role-admin { background: #fee2e2; color: #b91c1c; }
        .role-guru { background: #fef3c7; color: #92400e; }
        .role-panitia { background: #dcfce7; color: #166534; }
        .welcome-card { background: linear-gradient(135deg, var(--primary-color) 0%, #1565c0 100%); color: white; padding: 2.5rem; border-radius: var(--radius-lg); margin-bottom: 2rem; position: relative; overflow: hidden; box-shadow: var(--shadow-lg); }
        .welcome-card i { position: absolute; right: -20px; bottom: -20px; font-size: 10rem; opacity: 0.1; transform: rotate(-15deg); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="header">
                <div class="page-title"><?= $page_title ?></div>
                <div class="user-profile">
                    <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>
            
            <div class="content-body">
                <?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                    <div class="alert alert-error" style="margin-bottom: 1.5rem; background-color: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md);">
                        <i class='bx bx-shield-quarter' style="font-size: 1.2rem; vertical-align: middle;"></i> <strong>Akses Ditolak!</strong> Anda tidak memiliki izin untuk mengakses halaman tersebut.
                    </div>
                <?php endif; ?>

                <!-- Welcome Card Based on Role -->
                <div class="welcome-card">
                    <i class='bx bxs-graduation'></i>
                    <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!</h1>
                    <p style="opacity: 0.9; max-width: 600px;">
                        <?php if($role_id == 1): ?>
                            Pantau sistem dan kelola hak akses pengguna sekolah di sini.
                        <?php elseif($role_id == 2): ?>
                            Siapkan dokumen kegiatan (Proposal, Timeline, Laporan) dan bagikan pengalaman Anda.
                        <?php elseif($role_id == 3): ?>
                            Lakukan validasi dokumen dan susun evaluasi kegiatan.
                        <?php else: ?>
                            Lihat laporan capaian dan insight untuk pengambilan keputusan strategis sekolah.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- STATS ROW -->
                <div class="grid-stats">
                    <?php if($role_id == 1): // TAMPILAN ADMIN IT ?>
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class='bx bx-group'></i></div>
                            <div class="stat-info">
                                <h3><?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?></h3>
                                <p>Total Pengguna</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon yellow"><i class='bx bx-shield-quarter'></i></div>
                            <div class="stat-info">
                                <h3><?= $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn() ?></h3>
                                <p>Hak Akses (Roles)</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class='bx bx-data'></i></div>
                            <div class="stat-info">
                                <h3>Aktif</h3>
                                <p>Status Database</p>
                            </div>
                        </div>

                    <?php elseif($role_id == 4): // TAMPILAN YAYASAN ?>
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class='bx bx-calendar-check'></i></div>
                            <div class="stat-info">
                                <h3><?= $stats_kegiatan ?></h3>
                                <p>Total Kegiatan</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class='bx bx-star'></i></div>
                            <div class="stat-info">
                                <h3><?= number_format($stats_rating, 1) ?>/5.0</h3>
                                <p>Avg. Keberhasilan</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon yellow"><i class='bx bx-bulb'></i></div>
                            <div class="stat-info">
                                <h3><?= $stats_bp ?></h3>
                                <p>Best Practices</p>
                            </div>
                        </div>

                    <?php else: // TAMPILAN OPERASIONAL ?>
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class='bx bx-calendar-check'></i></div>
                            <div class="stat-info">
                                <h3><?= $stats_kegiatan ?></h3>
                                <p>Total Kegiatan</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon yellow"><i class='bx bxs-file-doc'></i></div>
                            <div class="stat-info">
                                <h3><?= $stats_docs ?></h3>
                                <p>Dokumen Knowledge</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class='bx bx-bulb'></i></div>
                            <div class="stat-info">
                                <h3><?= $stats_bp ?></h3>
                                <p>Best Practices</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- DETAIL ROW -->
                <div class="grid-stats" style="grid-template-columns: 2fr 1fr;">
                    
                    <!-- LEFT COLUMN -->
                    <div class="card">
                        <?php if($role_id == 4): // YAYASAN: REKAP EVALUASI ?>
                            <div class="card-header">
                                <h2 class="card-title">Evaluasi Strategis Terbaru</h2>
                            </div>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Kegiatan</th>
                                            <th>Rating</th>
                                            <th>Insight / Saran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_eval as $re): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?= htmlspecialchars($re['nama_kegiatan']) ?></td>
                                            <td><span class="badge badge-success">★ <?= $re['skor_rating'] ?>.0</span></td>
                                            <td style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars(substr($re['saran'], 0, 100)) ?>...</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: // ADMIN & OPERASIONAL: KEGIATAN TERBARU ?>
                            <div class="card-header">
                                <h2 class="card-title">Kegiatan Terbaru</h2>
                            </div>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nama Kegiatan</th>
                                            <th>Jenis</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($kegiatan_berjalan) > 0): ?>
                                            <?php foreach($kegiatan_berjalan as $kg): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($kg['nama_kegiatan']) ?></td>
                                                <td><?= htmlspecialchars($kg['jenis_kegiatan']) ?></td>
                                                <td>
                                                    <span class="badge <?= $kg['status'] == 'Selesai' ? 'badge-success' : ($kg['status'] == 'Berjalan' ? 'badge-warning' : 'badge-primary') ?>">
                                                        <?= htmlspecialchars($kg['status']) ?>
                                                    </span>
                                                </td>
                                                <td><a href="kegiatan_detail.php?id=<?= $kg['id'] ?>" style="color: var(--primary-color);">Detail</a></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" style="text-align:center;">Belum ada data.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="card">
                        <?php if($role_id == 1): // ADMIN: USER TERBARU ?>
                            <div class="card-header"><h2 class="card-title">User Terbaru</h2></div>
                            <div style="margin-top: 1rem;">
                                <?php 
                                $latest_users = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC LIMIT 5")->fetchAll();
                                foreach($latest_users as $lu): ?>
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1.2rem;">
                                        <div style="width: 35px; height: 35px; background: #e0e7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color);"><i class='bx bx-user'></i></div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($lu['nama_lengkap']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($lu['role_name']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif($role_id == 4): // YAYASAN: HIGHLIGHT BEST PRACTICE ?>
                            <div class="card-header"><h2 class="card-title">Highlight Best Practices</h2></div>
                            <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                                <?php foreach($recent_bp as $bp): ?>
                                    <div style="padding: 1rem; background: #fdfaf3; border-radius: 8px; border-left: 4px solid #f59e0b;">
                                        <h4 style="font-size: 0.85rem; margin-bottom: 5px;"><?= htmlspecialchars($bp['nama_kegiatan']) ?></h4>
                                        <p style="font-size: 0.75rem; color: #92400e; line-height: 1.4;">"<?= htmlspecialchars(substr($bp['solusi'], 0, 80)) ?>..."</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: // OPERASIONAL: KNOWLEDGE TERBARU ?>
                            <div class="card-header"><h2 class="card-title">Knowledge Terbaru</h2></div>
                            <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                                <?php foreach($knowledge_list as $kn): ?>
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <i class='bx bxs-file-doc' style="font-size: 1.5rem; color: var(--primary-color);"></i>
                                        <div>
                                            <h4 style="font-size: 0.85rem; margin-bottom: 2px;"><?= htmlspecialchars($kn['judul']) ?></h4>
                                            <p style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($kn['nama_lengkap']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
