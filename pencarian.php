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

$page_title = "Pencarian Pengetahuan Terpusat";
$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all'; // all, explicit, tacit
$category = $_GET['category'] ?? '';
$year = $_GET['year'] ?? '';

$explicit_results = [];
$tacit_results = [];

if ($query || $category || $year) {
    $search_param = "%$query%";
    
    // 1. Search Explicit Knowledge (Documents)
    if ($type == 'all' || $type == 'explicit') {
        $sql1 = "SELECT d.*, k.nama_kegiatan, k.tahun, k.jenis_kegiatan 
                 FROM knowledge_docs d 
                 JOIN kegiatan k ON d.kegiatan_id = k.id 
                 WHERE (d.judul LIKE ? OR k.nama_kegiatan LIKE ?)";
        $params1 = [$search_param, $search_param];
        
        if ($category) { $sql1 .= " AND k.jenis_kegiatan = ?"; $params1[] = $category; }
        if ($year) { $sql1 .= " AND k.tahun = ?"; $params1[] = $year; }
        
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute($params1);
        $explicit_results = $stmt1->fetchAll();
    }
    
    // 2. Search Tacit Knowledge (Lesson Learned + Forum)
    if ($type == 'all' || $type == 'tacit') {
        // A. Lesson Learned
        $sql2 = "SELECT ll.*, k.nama_kegiatan, k.tahun, k.jenis_kegiatan 
                 FROM lesson_learned ll 
                 JOIN kegiatan k ON ll.kegiatan_id = k.id 
                 WHERE (ll.kendala LIKE ? OR ll.solusi LIKE ? OR k.nama_kegiatan LIKE ?)";
        $params2 = [$search_param, $search_param, $search_param];
        
        if ($category) { $sql2 .= " AND k.jenis_kegiatan = ?"; $params2[] = $category; }
        if ($year) { $sql2 .= " AND k.tahun = ?"; $params2[] = $year; }
        
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute($params2);
        $tacit_results = $stmt2->fetchAll();

        // B. Forum Discussions & Comments
        $sql3 = "SELECT f.id as diskusi_id, f.judul, f.created_at, k.nama_kegiatan, k.tahun,
                 (SELECT content FROM forum_comments WHERE diskusi_id = f.id AND content LIKE ? LIMIT 1) as snippet
                 FROM forum_diskusi f
                 JOIN kegiatan k ON f.kegiatan_id = k.id
                 WHERE (f.judul LIKE ? OR EXISTS (SELECT 1 FROM forum_comments WHERE diskusi_id = f.id AND content LIKE ?))";
        $params3 = [$search_param, $search_param, $search_param];
        
        if ($category) { $sql3 .= " AND k.jenis_kegiatan = ?"; $params3[] = $category; }
        if ($year) { $sql3 .= " AND k.tahun = ?"; $params3[] = $year; }

        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute($params3);
        $forum_results = $stmt3->fetchAll();
    }
}

// Get filter options
$categories = $pdo->query("SELECT DISTINCT jenis_kegiatan FROM kegiatan WHERE jenis_kegiatan != ''")->fetchAll();
$years = $pdo->query("SELECT DISTINCT tahun FROM kegiatan ORDER BY tahun DESC")->fetchAll();
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
        .search-container { background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 2rem; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .result-section { margin-top: 2rem; }
        .result-card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; border: 1px solid #e2e8f0; transition: all 0.3s; }
        .result-card:hover { border-color: var(--primary-color); box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .type-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
        .badge-tacit { background: #e0f2fe; color: #0369a1; }
        .badge-explicit { background: #f0fdf4; color: #15803d; }
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
                <!-- FORM PENCARIAN -->
                <div class="search-container">
                    <form action="pencarian.php" method="GET">
                        <div style="position: relative;">
                            <i class='bx bx-search' style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 1.5rem; color: var(--text-secondary);"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Apa yang ingin Anda cari hari ini?" 
                                   style="width: 100%; padding: 15px 15px 15px 50px; border-radius: 12px; border: 1px solid #cbd5e1; font-size: 1.1rem; outline: none; border-bottom: 3px solid var(--primary-color);">
                        </div>

                        <div class="filter-grid">
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 0.85rem; color: #64748b;">Tipe Pengetahuan</label>
                                <select name="type" class="form-control">
                                    <option value="all" <?= $type == 'all' ? 'selected' : '' ?>>Semua Pengetahuan</option>
                                    <option value="explicit" <?= $type == 'explicit' ? 'selected' : '' ?>>Explicit (Dokumen)</option>
                                    <option value="tacit" <?= $type == 'tacit' ? 'selected' : '' ?>>Tacit (Pengalaman)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 0.85rem; color: #64748b;">Kategori Kegiatan</label>
                                <select name="category" class="form-control">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['jenis_kegiatan'] ?>" <?= $category == $cat['jenis_kegiatan'] ? 'selected' : '' ?>><?= $cat['jenis_kegiatan'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 0.85rem; color: #64748b;">Tahun</label>
                                <select name="year" class="form-control">
                                    <option value="">Semua Tahun</option>
                                    <?php foreach($years as $y): ?>
                                        <option value="<?= $y['tahun'] ?>" <?= $year == $y['tahun'] ? 'selected' : '' ?>><?= $y['tahun'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem; padding: 12px 25px;">Mulai Pencarian</button>
                    </form>
                </div>

                <!-- HASIL PENCARIAN -->
                <?php if($query || $category || $year): ?>
                <div class="result-section">
                    <h3 style="margin-bottom: 2rem; color: var(--text-primary);">Ditemukan <?= count($explicit_results) + count($tacit_results) + count($forum_results) ?> hasil untuk pencarian Anda:</h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
                        
                        <!-- KOLOM KIRI: EXPLICIT -->
                        <div class="column-explicit">
                            <h3 style="font-size: 1rem; color: #15803d; margin-bottom: 1rem; border-bottom: 2px solid #bcf0da; padding-bottom: 8px;">
                                <i class='bx bx-folder-open'></i> Explicit Knowledge (Dokumen)
                            </h3>
                            <?php if(count($explicit_results) > 0): ?>
                                <?php foreach($explicit_results as $er): ?>
                                    <div class="result-card" style="border-top: 3px solid #10b981;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                            <span class="type-badge badge-explicit">DOCUMENT</span>
                                            <span style="font-size: 0.8rem; color: var(--text-secondary);"><?= $er['tahun'] ?></span>
                                        </div>
                                        <h4 style="margin-bottom: 8px; font-size: 0.95rem;"><i class='bx bxs-file-pdf'></i> <?= htmlspecialchars($er['judul']) ?></h4>
                                        <p style="font-size: 0.8rem; color: var(--text-secondary);">Event: <?= htmlspecialchars($er['nama_kegiatan']) ?></p>
                                        <div style="margin-top: 1rem;">
                                            <a href="knowledge.php?search=<?= urlencode($er['judul']) ?>" style="color: var(--primary-color); font-weight: 600; text-decoration: none; font-size: 0.85rem;">Lihat Dokumen &rarr;</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 2rem; background: #f8fafc; border-radius: 12px;">Tidak ditemukan dokumen.</p>
                            <?php endif; ?>
                        </div>

                        <!-- KOLOM KANAN: TACIT -->
                        <div class="column-tacit">
                            <h3 style="font-size: 1rem; color: #0369a1; margin-bottom: 1rem; border-bottom: 2px solid #bae6fd; padding-bottom: 8px;">
                                <i class='bx bx-bulb'></i> Tacit Knowledge (Pengalaman & Diskusi)
                            </h3>
                            
                            <!-- Lesson Learned Results -->
                            <?php foreach($tacit_results as $tr): ?>
                                <div class="result-card" style="border-top: 3px solid #0ea5e9;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span class="type-badge badge-tacit">EXPERIENCE</span>
                                        <span style="font-size: 0.8rem; color: var(--text-secondary);"><?= $tr['tahun'] ?></span>
                                    </div>
                                    <h4 style="margin-bottom: 8px; font-size: 0.95rem;"><i class='bx bx-bulb'></i> Kendala: <?= htmlspecialchars(substr($tr['kendala'], 0, 80)) ?>...</h4>
                                    <p style="font-size: 0.8rem; color: var(--text-secondary);">Event: <?= htmlspecialchars($tr['nama_kegiatan']) ?></p>
                                    <div style="margin-top: 1rem;">
                                        <a href="lesson_learned.php?search=<?= urlencode($tr['nama_kegiatan']) ?>" style="color: #0369a1; font-weight: 600; text-decoration: none; font-size: 0.85rem;">Pelajari Pengalaman &rarr;</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Forum Results -->
                            <?php foreach($forum_results as $fr): ?>
                                <div class="result-card" style="border-top: 3px solid #6366f1;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span class="type-badge" style="background: #e0e7ff; color: #3730a3;">FORUM DISKUSI</span>
                                        <span style="font-size: 0.8rem; color: var(--text-secondary);"><?= $fr['tahun'] ?></span>
                                    </div>
                                    <h4 style="margin-bottom: 8px; font-size: 0.95rem;"><i class='bx bx-conversation'></i> <?= htmlspecialchars($fr['judul']) ?></h4>
                                    <?php if($fr['snippet']): ?>
                                        <p style="font-size: 0.75rem; color: #475569; background: #f8fafc; padding: 6px; border-radius: 4px; margin-bottom: 8px;">
                                            "<?= htmlspecialchars(substr($fr['snippet'], 0, 100)) ?>..."
                                        </p>
                                    <?php endif; ?>
                                    <p style="font-size: 0.8rem; color: var(--text-secondary);">Event: <?= htmlspecialchars($fr['nama_kegiatan']) ?></p>
                                    <div style="margin-top: 1rem;">
                                        <a href="forum_detail.php?id=<?= $fr['diskusi_id'] ?>" style="color: #3730a3; font-weight: 600; text-decoration: none; font-size: 0.85rem;">Lihat Diskusi &rarr;</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if(count($tacit_results) == 0 && count($forum_results) == 0): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 2rem; background: #f8fafc; border-radius: 12px;">Tidak ditemukan pengalaman/diskusi.</p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <?php if(count($explicit_results) == 0 && count($tacit_results) == 0 && count($forum_results) == 0): ?>
                        <div style="text-align: center; padding: 4rem; color: var(--text-secondary);">
                            <i class='bx bx-search-alt' style="font-size: 4rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                            <p>Maaf, tidak ada pengetahuan yang cocok dengan filter Anda.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 5rem; color: var(--text-secondary); opacity: 0.5;">
                    <i class='bx bx-info-circle' style="font-size: 4rem; margin-bottom: 1rem;"></i>
                    <p>Gunakan form di atas untuk mencari pengetahuan tacit dan explicit.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
