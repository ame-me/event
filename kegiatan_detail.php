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

if (!isset($_GET['id'])) {
    header("Location: kegiatan.php");
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE id = ?");
$stmt->execute([$id]);
$kegiatan = $stmt->fetch();

if (!$kegiatan) {
    echo "Kegiatan tidak ditemukan.";
    exit();
}

// Handle Status Update (Admin IT, Manajemen, & Panitia)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status' && $_SESSION['role_id'] <= 3) {
    $new_status = $_POST['status'];
    $update = $pdo->prepare("UPDATE kegiatan SET status = ? WHERE id = ?");
    $update->execute([$new_status, $id]);
    header("Location: kegiatan_detail.php?id=" . $id . "&msg=status_updated");
    exit();
}

// Fetch related documents
$doc_stmt = $pdo->prepare("SELECT * FROM knowledge_docs WHERE kegiatan_id = ?");
$doc_stmt->execute([$id]);
$related_docs = $doc_stmt->fetchAll();

// Fetch related lesson learned
$ll_stmt = $pdo->prepare("SELECT ll.*, u.nama_lengkap FROM lesson_learned ll JOIN users u ON ll.user_id = u.id WHERE ll.kegiatan_id = ?");
$ll_stmt->execute([$id]);
$related_lessons = $ll_stmt->fetchAll();

// Fetch related evaluation
$eval_stmt = $pdo->prepare("SELECT e.*, u.nama_lengkap FROM evaluasi e JOIN users u ON e.user_id = u.id WHERE e.kegiatan_id = ?");
$eval_stmt->execute([$id]);
$related_evals = $eval_stmt->fetchAll();

$page_title = "Detail Kegiatan: " . $kegiatan['nama_kegiatan'];
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
        .detail-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-top: 1rem; }
        .info-card { background: #f8fafc; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
        .section-title { font-size: 1.1rem; font-weight: 600; color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .data-item { margin-bottom: 1rem; }
        .data-label { font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: bold; }
        .data-value { color: var(--text-primary); font-weight: 500; }
        .knowledge-item { background: white; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1rem; transition: 0.2s; }
        .knowledge-item:hover { border-color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div class="page-title">
                    <?php
                    $back_url = "dashboard.php";
                    if (has_permission('kegiatan.php')) {
                        $back_url = "kegiatan.php";
                    } elseif (has_permission('evaluasi.php')) {
                        $back_url = "evaluasi.php";
                    }
                    ?>
                    <a href="<?= $back_url ?>" style="color:var(--text-secondary); text-decoration:none;"><i class='bx bx-arrow-back'></i> Kembali</a> &nbsp;|&nbsp; <?= $page_title ?>
                </div>
                <div class="user-profile"><span><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span></div>
            </header>

            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></h2>
                        <?php 
                            $b = 'badge-primary';
                            if($kegiatan['status'] == 'Berjalan') $b = 'badge-warning';
                            if($kegiatan['status'] == 'Selesai') $b = 'badge-success';
                        ?>
                        <span class="badge <?= $b ?>"><?= htmlspecialchars($kegiatan['status']) ?></span>
                    </div>
                    
                    <div class="detail-grid">
                        <!-- Sidebar Info -->
                        <div class="info-card">
                            <div class="section-title"><i class='bx bx-info-circle'></i> Informasi Dasar</div>
                            <div class="data-item">
                                <div class="data-label">Kategori</div>
                                <div class="data-value"><?= htmlspecialchars($kegiatan['jenis_kegiatan']) ?></div>
                            </div>
                            <div class="data-item">
                                <div class="data-label">Tahun Ajaran</div>
                                <div class="data-value"><?= htmlspecialchars($kegiatan['tahun']) ?></div>
                            </div>
                            <div class="data-item">
                                <div class="data-label">Dibuat Pada</div>
                                <div class="data-value"><?= date('d M Y', strtotime($kegiatan['created_at'])) ?></div>
                            </div>

                            <?php if($_SESSION['role_id'] <= 3): ?>
                            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed #cbd5e1;">
                                <div class="data-label" style="margin-bottom: 8px;">Ubah Status Kegiatan</div>
                                <form action="kegiatan_detail.php?id=<?= $id ?>" method="POST">
                                    <input type="hidden" name="action" value="update_status">
                                    <select name="status" class="form-control" onchange="this.form.submit()" style="font-size: 0.85rem; padding: 5px;">
                                        <option value="Perencanaan" <?= $kegiatan['status'] == 'Perencanaan' ? 'selected' : '' ?>>Perencanaan</option>
                                        <option value="Berjalan" <?= $kegiatan['status'] == 'Berjalan' ? 'selected' : '' ?>>Berjalan</option>
                                        <option value="Selesai" <?= $kegiatan['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                </form>
                            </div>
                            <?php endif; ?>

                            <div style="margin-top: 2rem;">
                                <?php
                                $back_url = "dashboard.php";
                                if (has_permission('kegiatan.php')) {
                                    $back_url = "kegiatan.php";
                                } elseif (has_permission('evaluasi.php')) {
                                    $back_url = "evaluasi.php";
                                }
                                ?>
                                <a href="<?= $back_url ?>" class="btn" style="background: #e2e8f0; color: #1e293b; width: 100%; text-decoration: none; text-align: center; display: block;">Kembali</a>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div>
                            <div class="section-title"><i class='bx bx-align-left'></i> Deskripsi Kegiatan</div>
                            <p style="line-height: 1.6; color: #475569; margin-bottom: 2rem;"><?= nl2br(htmlspecialchars($kegiatan['deskripsi'] ?: 'Tidak ada deskripsi.')) ?></p>

                            <!-- Knowledge Documents Section -->
                            <div class="section-title"><i class='bx bx-file'></i> Dokumen Terkait (Explicit)</div>
                            <?php if(count($related_docs) > 0): ?>
                                <?php foreach($related_docs as $doc): ?>
                                <div class="knowledge-item">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i class='bx bxs-file-doc' style="font-size: 1.5rem; color: var(--primary-color);"></i>
                                            <span style="font-weight: 500;"><?= htmlspecialchars($doc['judul']) ?></span>
                                        </div>
                                        <a href="<?= $doc['file_path'] ?>" download class="btn" style="width: auto; padding: 4px 10px; font-size: 0.8rem;"><i class='bx bx-download'></i> Download</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 2rem;">Belum ada dokumen yang diunggah.</p>
                            <?php endif; ?>

                            <!-- Experiences Section -->
                            <div class="section-title"><i class='bx bx-bulb'></i> Lesson Learned (Tacit)</div>
                            <?php if(count($related_lessons) > 0): ?>
                                <?php foreach($related_lessons as $ll): ?>
                                <div class="knowledge-item" style="border-left: 4px solid var(--secondary-color);">
                                    <p style="font-size: 0.85rem; margin-bottom: 5px;"><strong>Kendala:</strong> <?= htmlspecialchars($ll['kendala']) ?></p>
                                    <p style="font-size: 0.85rem; color: #10b981;"><strong>Solusi:</strong> <?= htmlspecialchars($ll['solusi']) ?></p>
                                    <div style="margin-top: 8px; font-size: 0.75rem; color: var(--text-secondary);">Oleh: <?= htmlspecialchars($ll['nama_lengkap']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 2rem;">Belum ada pengalaman yang dibagikan.</p>
                            <?php endif; ?>

                            <!-- Evaluation Section -->
                            <div class="section-title"><i class='bx bx-check-shield'></i> Hasil Evaluasi</div>
                            <?php if(count($related_evals) > 0): ?>
                                <?php foreach($related_evals as $ev): ?>
                                <div class="knowledge-item" style="background: #f0fdf4;">
                                    <div style="display: flex; align-items: center; gap: 5px; color: #f59e0b; margin-bottom: 8px;">
                                        <?php for($i=1; $i<=5; $i++) echo $i<=$ev['skor_rating'] ? "<i class='bx bxs-star'></i>" : "<i class='bx bx-star'></i>"; ?>
                                        <span style="color: var(--text-primary); font-weight: 600; margin-left: 5px;"><?= $ev['skor_rating'] ?>/5</span>
                                    </div>
                                    <p style="font-size: 0.85rem;"><strong>Keberhasilan:</strong> <?= htmlspecialchars($ev['keberhasilan']) ?></p>
                                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 5px;"><strong>Saran:</strong> <?= htmlspecialchars($ev['saran']) ?></p>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 0.9rem; color: var(--text-secondary);">Belum ada hasil evaluasi.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
