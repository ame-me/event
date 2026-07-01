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

$query = $_GET['q'] ?? '';
$page_title = "Hasil Pencarian: " . htmlspecialchars($query);

$explicit_results = [];
$tacit_results = [];

if ($query) {
    $search_param = "%$query%";
    
    // 1. Search Explicit Knowledge (Documents)
    $stmt1 = $pdo->prepare("SELECT d.*, k.nama_kegiatan, k.tahun 
                            FROM knowledge_docs d 
                            JOIN kegiatan k ON d.kegiatan_id = k.id 
                            WHERE d.judul LIKE ? OR k.nama_kegiatan LIKE ? OR d.tipe_dokumen LIKE ?");
    $stmt1->execute([$search_param, $search_param, $search_param]);
    $explicit_results = $stmt1->fetchAll();
    
    // 2. Search Tacit Knowledge (Lesson Learned)
    $stmt2 = $pdo->prepare("SELECT ll.*, k.nama_kegiatan, k.tahun 
                            FROM lesson_learned ll 
                            JOIN kegiatan k ON ll.kegiatan_id = k.id 
                            WHERE ll.kendala LIKE ? OR ll.solusi LIKE ? OR ll.rekomendasi LIKE ? OR k.nama_kegiatan LIKE ?");
    $stmt2->execute([$search_param, $search_param, $search_param, $search_param]);
    $tacit_results = $stmt2->fetchAll();
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
        .search-section { margin-bottom: 2.5rem; }
        .section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        .result-card { background: white; border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem; border: 1px solid #e2e8f0; transition: transform 0.2s; }
        .result-card:hover { transform: translateX(5px); border-left: 4px solid var(--primary-color); }
        .badge-tacit { background: #e0f2fe; color: #0369a1; }
        .badge-explicit { background: #f0fdf4; color: #15803d; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
                <div class="page-title"><i class='bx bx-search'></i> Hasil Pencarian</div>
                <div class="user-profile"><span><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span></div>
            </header>

            <div class="content-body">
                <div style="margin-bottom: 2rem;">
                    <h2 style="font-size: 1.25rem; color: var(--text-secondary);">Menampilkan hasil untuk: <span style="color: var(--primary-color);">"<?= htmlspecialchars($query) ?>"</span></h2>
                    <p style="font-size: 0.9rem; color: #64748b;">Ditemukan <?= count($explicit_results) ?> Dokumen dan <?= count($tacit_results) ?> Pengalaman.</p>
                </div>

                <!-- EXPLICIT KNOWLEDGE RESULTS -->
                <div class="search-section">
                    <div class="section-header">
                        <i class='bx bx-folder-open' style="font-size: 1.5rem; color: #15803d;"></i>
                        <h3 style="color: #15803d;">Explicit Knowledge (Dokumen)</h3>
                    </div>
                    <?php if(count($explicit_results) > 0): ?>
                        <?php foreach($explicit_results as $er): ?>
                            <div class="result-card">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h4 style="font-size: 1.05rem; margin-bottom: 4px;"><?= htmlspecialchars($er['judul']) ?></h4>
                                        <p style="font-size: 0.85rem; color: #64748b;">Event: <?= htmlspecialchars($er['nama_kegiatan']) ?> (<?= $er['tahun'] ?>)</p>
                                    </div>
                                    <span class="badge badge-explicit"><?= htmlspecialchars($er['tipe_dokumen']) ?></span>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <a href="knowledge.php?search=<?= urlencode($er['judul']) ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.75rem; width: auto; text-decoration: none;">Lihat di Repository</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-secondary); padding: 1rem;">Tidak ada dokumen yang sesuai.</p>
                    <?php endif; ?>
                </div>

                <!-- TACIT KNOWLEDGE RESULTS -->
                <div class="search-section">
                    <div class="section-header">
                        <i class='bx bx-bulb' style="font-size: 1.5rem; color: #0369a1;"></i>
                        <h3 style="color: #0369a1;">Tacit Knowledge (Pengalaman)</h3>
                    </div>
                    <?php if(count($tacit_results) > 0): ?>
                        <?php foreach($tacit_results as $tr): ?>
                            <div class="result-card">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h4 style="font-size: 1.05rem; margin-bottom: 4px;">Kendala: <?= htmlspecialchars(substr($tr['kendala'], 0, 80)) ?>...</h4>
                                        <p style="font-size: 0.85rem; color: #64748b;">Event: <?= htmlspecialchars($tr['nama_kegiatan']) ?> (<?= $tr['tahun'] ?>)</p>
                                    </div>
                                    <?php if($tr['is_best_practice']): ?>
                                        <span class="badge" style="background: #fef3c7; color: #92400e;"><i class='bx bxs-star'></i> Best Practice</span>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 0.8rem; font-size: 0.9rem; color: #334155;">
                                    <strong>Solusi:</strong> <?= htmlspecialchars(substr($tr['solusi'], 0, 100)) ?>...
                                </div>
                                <div style="margin-top: 1rem;">
                                    <a href="lesson_learned.php?search=<?= urlencode($tr['nama_kegiatan']) ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.75rem; width: auto; text-decoration: none; background: #0369a1;">Lihat Detail Pengalaman</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-secondary); padding: 1rem;">Tidak ada pengalaman yang sesuai.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
