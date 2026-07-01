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

$page_title = "Lesson Learned (Pengalaman)";

// Ambil daftar kegiatan untuk dropdown input
$kegiatan_stmt = $pdo->query("SELECT id, nama_kegiatan, tahun FROM kegiatan ORDER BY tahun DESC, id DESC");
$list_kegiatan = $kegiatan_stmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $kegiatan_id = $_POST['kegiatan_id'];
    $kendala = $_POST['kendala'];
    $solusi = $_POST['solusi'];
    $rekomendasi = $_POST['rekomendasi'];
    $is_best_practice = isset($_POST['is_best_practice']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    $insert = $pdo->prepare("INSERT INTO lesson_learned (kegiatan_id, user_id, kendala, solusi, rekomendasi, is_best_practice) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->execute([$kegiatan_id, $user_id, $kendala, $solusi, $rekomendasi, $is_best_practice]);
    
    header("Location: lesson_learned.php?msg=success");
    exit();
}

// Handle Toggle Best Practice
if (isset($_GET['toggle_bp']) && ($_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 3)) {
    $id = $_GET['toggle_bp'];
    $stmt = $pdo->prepare("UPDATE lesson_learned SET is_best_practice = NOT is_best_practice WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: lesson_learned.php?msg=updated");
    exit();
}

// Ambil data Lesson Learned dengan Pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT ll.*, k.nama_kegiatan, k.tahun, k.jenis_kegiatan, u.nama_lengkap 
         FROM lesson_learned ll 
         JOIN kegiatan k ON ll.kegiatan_id = k.id 
         JOIN users u ON ll.user_id = u.id";

$params = [];
if ($search) {
    $sql .= " WHERE ll.kendala LIKE ? OR ll.solusi LIKE ? OR k.nama_kegiatan LIKE ?";
    $sp = "%$search%";
    $params[] = $sp; $params[] = $sp; $params[] = $sp;
}

$sql .= " ORDER BY ll.created_at DESC";
$ll_stmt = $pdo->prepare($sql);
$ll_stmt->execute($params);
$lessons = $ll_stmt->fetchAll();
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
         .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
         .modal.active { display: flex; }
         .modal-content { background-color: var(--surface-color); padding: 2rem; border-radius: var(--radius-lg); width: 100%; max-width: 600px; position: relative; max-height: 90vh; overflow-y: auto; }
         .close-btn { position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
         .ll-card { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); }
         .ll-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
         .ll-meta { font-size: 0.85rem; color: var(--text-secondary); margin-top: 5px; }
         .ll-content h4 { color: #334155; font-size: 0.95rem; margin-bottom: 0.5rem; margin-top: 1rem; display: flex; align-items: center; gap: 5px;}
         .ll-content p { color: #475569; font-size: 0.95rem; line-height: 1.6; padding-left: 1.5rem; margin-bottom: 0.5rem;}
         .best-practice-ribbon { background: #10b981; color: white; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; }
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
                    <span style="font-weight: 500;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>
            
            <div class="content-body">
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                    <div class="alert badge-success" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #10b981;">
                        Pengalaman (Tacit Knowledge) berhasil dibagikan!
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <h2 style="font-size: 1.5rem; color: var(--text-primary);">Daftar Pengalaman Panitia</h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Ubah pengalaman tacit menjadi explicit untuk referensi masa depan.</p>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <!-- Search Form -->
                        <form action="lesson_learned.php" method="GET" style="display: flex; gap: 5px; width: 250px;">
                            <div style="position: relative; width: 100%;">
                                <i class='bx bx-search' style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                                <input type="text" name="search" placeholder="Cari pengalaman..." value="<?= htmlspecialchars($search) ?>" 
                                       style="width: 100%; padding: 8px 12px 8px 35px; border: 1px solid #e2e8f0; border-radius: var(--radius-md); font-size: 0.8rem;">
                            </div>
                        </form>

                        <?php if($_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 3): // Manajemen & Panitia bisa input lesson learned ?>
                        <button class="btn btn-primary" onclick="document.getElementById('modalTambah').classList.add('active')" style="width: auto;">
                            <i class='bx bx-pencil' ></i> Bagikan Pengalaman Baru
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lesson-learned-list">
                    <?php if (count($lessons) > 0): ?>
                        <?php foreach ($lessons as $ll): ?>
                        <div class="ll-card">
                            <div class="ll-header">
                                <div>
                                    <h3 style="color: var(--primary-color); margin-bottom: 5px;">Konteks: <?= htmlspecialchars($ll['nama_kegiatan']) ?></h3>
                                    <div class="ll-meta">
                                        <span class="badge" style="background: #f1f5f9; color: #475569;"><?= htmlspecialchars($ll['jenis_kegiatan']) ?></span>
                                        <span style="margin-left: 10px;"><i class='bx bx-user'></i> <?= htmlspecialchars($ll['nama_lengkap']) ?> (Panitia)</span>
                                        <span style="margin-left: 10px;"><i class='bx bx-calendar'></i> Th: <?= htmlspecialchars($ll['tahun']) ?></span>
                                    </div>
                                </div>
                                <?php if($_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 3): // Manajemen & Panitia bisa tandai Best Practice ?>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if($ll['is_best_practice']): ?>
                                            <div class="best-practice-ribbon"><i class='bx bxs-star'></i> Best Practice</div>
                                            <a href="lesson_learned.php?toggle_bp=<?= $ll['id'] ?>" style="font-size: 0.75rem; color: #ef4444; text-decoration: none;">(Hapus)</a>
                                        <?php else: ?>
                                            <a href="lesson_learned.php?toggle_bp=<?= $ll['id'] ?>" class="btn" style="padding: 4px 10px; font-size: 0.7rem; background: #e2e8f0; color: #475569; width: auto; text-decoration: none;">Tandai Best Practice</a>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif($ll['is_best_practice']): ?>
                                    <div class="best-practice-ribbon"><i class='bx bxs-star'></i> Best Practice</div>
                                <?php endif; ?>
                            </div>
                            <div class="ll-content">
                                <h4><i class='bx bx-error-circle' style="color: #ef4444;"></i> Kendala yang Terjadi:</h4>
                                <p><?= nl2br(htmlspecialchars($ll['kendala'])) ?></p>
                                
                                <h4><i class='bx bx-check-circle' style="color: #10b981;"></i> Solusi yang Dilakukan:</h4>
                                <p><?= nl2br(htmlspecialchars($ll['solusi'])) ?></p>
                                
                                <h4><i class='bx bx-bulb' style="color: var(--secondary-color);"></i> Rekomendasi untuk Kepanitiaan Mendatang:</h4>
                                <p style="font-weight: 500;"><?= nl2br(htmlspecialchars($ll['rekomendasi'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card" style="text-align: center; padding: 3rem;">
                            <i class='bx bx-ghost' style="font-size: 4rem; color: #cbd5e1;"></i>
                            <h3 style="margin-top: 1rem; color: var(--text-secondary);">Belum ada pengalaman yang dibagikan.</h3>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <!-- Modal Form Lesson Learned -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('modalTambah').classList.remove('active')">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Input Lesson Learned</h2>
            
            <form action="lesson_learned.php" method="POST">
                <input type="hidden" name="action" value="save">
                
                <div class="form-group">
                    <label>Pilih Kegiatan (Konteks Pengalaman)</label>
                    <select name="kegiatan_id" class="form-control" required style="appearance: auto;">
                        <option value="">-- Kegiatan --</option>
                        <?php foreach($list_kegiatan as $keg): ?>
                            <option value="<?= $keg['id'] ?>"><?= htmlspecialchars($keg['nama_kegiatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>1. Kendala / Masalah yang Terjadi</label>
                    <textarea name="kendala" class="form-control" rows="3" required placeholder="Jelaskan secara spesifik apa yang tidak berjalan sesuai rencana..." style="resize: vertical;"></textarea>
                </div>

                <div class="form-group">
                    <label>2. Solusi yang Dilakukan (Saat Itu)</label>
                    <textarea name="solusi" class="form-control" rows="3" required placeholder="Bagaimana panitia menyelesaikan masalah tersebut?" style="resize: vertical;"></textarea>
                </div>

                <div class="form-group">
                    <label>3. Rekomendasi/Saran untuk Aspek Ini</label>
                    <textarea name="rekomendasi" class="form-control" rows="2" required placeholder="Saran agar masalah ini tidak terulang di masa depan..." style="resize: vertical;"></textarea>
                </div>

                <!-- Best Practice Highlight -->
                <div class="form-group" style="display: flex; align-items: center; gap: 10px; background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: var(--radius-md); border: 1px dashed #10b981;">
                    <input type="checkbox" id="is_best_practice" name="is_best_practice" value="1" style="width: 18px; height: 18px; accent-color: #10b981;">
                    <label for="is_best_practice" style="margin-bottom: 0; cursor: pointer; color: #065f46;">Tandai pengalaman ini sebagai <strong>"Best Practice"</strong> agar disorot oleh sistem.</label>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" class="btn" style="background: #e2e8f0; color: #1e293b;" onclick="document.getElementById('modalTambah').classList.remove('active')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Pengalaman</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
