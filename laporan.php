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

$page_title = "Laporan & Insight Knowledge";

// Analytics Data fetching
$total_kegiatan = $pdo->query("SELECT COUNT(*) FROM kegiatan")->fetchColumn();
$total_docs = $pdo->query("SELECT COUNT(*) FROM knowledge_docs")->fetchColumn();
$total_ll = $pdo->query("SELECT COUNT(*) FROM lesson_learned")->fetchColumn();
$total_eval = $pdo->query("SELECT COUNT(*) FROM evaluasi")->fetchColumn();

// Rata-rata rating keberhasilan per jenis kegiatan
$stats_kategori = $pdo->query("SELECT k.jenis_kegiatan, AVG(e.skor_rating) as avg_rating, COUNT(e.id) as total_eval
                           FROM evaluasi e 
                           JOIN kegiatan k ON e.kegiatan_id = k.id 
                           GROUP BY k.jenis_kegiatan")->fetchAll();

// Best practice terbaru
$best_practices = $pdo->query("SELECT ll.*, k.nama_kegiatan 
                               FROM lesson_learned ll 
                               JOIN kegiatan k ON ll.kegiatan_id = k.id 
                               WHERE ll.is_best_practice = 1 
                               ORDER BY ll.created_at DESC LIMIT 3")->fetchAll();
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
         .insight-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
         .stat-bar-container { background: #f1f5f9; border-radius: var(--radius-md); height: 24px; width: 100%; margin-top: 5px; overflow: hidden; position: relative; }
         .stat-bar { background: var(--primary-color); height: 100%; border-radius: var(--radius-md); display: flex; align-items: center; padding-left: 10px; color: white; font-size: 0.75rem; font-weight: bold; }
         .chart-row { margin-bottom: 1.5rem; }
         .chart-label { display: flex; justify-content: space-between; font-weight: 500; font-size: 0.9rem; color: var(--text-primary); }
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
                        <h2 style="font-size: 1.5rem; color: var(--text-primary);">Analitik Knowledge Management</h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Statistik penggunaan dan kualitas acara sekolah secara keseluruhan.</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.print()" style="width: auto; background: #10b981; border: none;">
                        <i class='bx bx-printer'></i> Export PDF
                    </button>
                </div>

                <div class="grid-stats">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class='bx bx-folder'></i></div>
                        <div class="stat-info">
                            <h3><?= $total_docs ?></h3>
                            <p>Total Knowledge Docs</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon yellow"><i class='bx bx-bulb'></i></div>
                        <div class="stat-info">
                            <h3><?= $total_ll ?></h3>
                            <p>Lesson Learned Masuk</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class='bx bx-check-shield'></i></div>
                        <div class="stat-info">
                            <h3><?= $total_eval ?></h3>
                            <p>Event Tengevaluasi</p>
                        </div>
                    </div>
                </div>

                <div class="insight-grid">
                    <!-- Analitik Per Kategori -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Rata-Rata Keberhasilan per Kategori Event</h2>
                        </div>
                        <?php foreach($stats_kategori as $sk): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 600;"><?= htmlspecialchars($sk['jenis_kegiatan']) ?> <small style="font-weight: normal; color: var(--text-secondary);">Melihat <?= $sk['total_eval'] ?> evaluasi</small></span>
                                <span style="color: #f59e0b; font-weight: bold;"><i class='bx bxs-star'></i> <?= number_format($sk['avg_rating'], 1) ?> / 5.0</span>
                            </div>
                            <div style="height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                <div style="width: <?= ($sk['avg_rating']/5)*100 ?>%; height: 100%; background: var(--success-color);"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- BARU: Rincian Per Kegiatan -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Rincian Performa Per Kegiatan</h2>
                        </div>
                        <div class="table-responsive">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                                        <th style="padding: 12px;">Nama Kegiatan</th>
                                        <th style="padding: 12px;">Kategori</th>
                                        <th style="padding: 12px; text-align: center;">Rating</th>
                                        <th style="padding: 12px; text-align: center;">Dokumen</th>
                                        <th style="padding: 12px; text-align: center;">Lesson Learned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stmt_detail = $pdo->query("SELECT k.nama_kegiatan, k.jenis_kegiatan, k.tahun,
                                                               (SELECT skor_rating FROM evaluasi WHERE kegiatan_id = k.id LIMIT 1) as rating,
                                                               (SELECT COUNT(*) FROM knowledge_docs WHERE kegiatan_id = k.id) as docs_count,
                                                               (SELECT COUNT(*) FROM lesson_learned WHERE kegiatan_id = k.id) as ll_count
                                                               FROM kegiatan k ORDER BY k.tahun DESC");
                                    $details = $stmt_detail->fetchAll();
                                    foreach($details as $d):
                                    ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 12px;">
                                            <strong><?= htmlspecialchars($d['nama_kegiatan']) ?></strong><br>
                                            <small style="color: var(--text-secondary);"><?= $d['tahun'] ?></small>
                                        </td>
                                        <td style="padding: 12px;"><?= htmlspecialchars($d['jenis_kegiatan']) ?></td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php if($d['rating']): ?>
                                                <span style="color: #f59e0b; font-weight: bold;"><?= $d['rating'] ?>/5</span>
                                            <?php else: ?>
                                                <span style="color: var(--text-secondary); font-size: 0.8rem;">Belum Dinilai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;"><?= $d['docs_count'] ?></td>
                                        <td style="padding: 12px; text-align: center;"><?= $d['ll_count'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Highlight Best Practice -->
                    <div class="card">
                        <h3 class="card-title" style="margin-bottom: 1.5rem; color: #10b981;"><i class='bx bxs-star'></i> Top Best Practices</h3>
                        <div>
                            <?php if(count($best_practices) > 0): ?>
                                <?php foreach($best_practices as $bp): ?>
                                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;">
                                    <h4 style="font-size: 0.95rem; color: var(--primary-color); margin-bottom: 4px;"><?= htmlspecialchars($bp['nama_kegiatan']) ?></h4>
                                    <p style="font-size: 0.85rem; color: #475569; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                        "<?= htmlspecialchars($bp['solusi']) ?>"
                                    </p>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 0.85rem; color: var(--text-secondary);">Belum ada status Best Practice yang ditandai dari Lesson Learned.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
