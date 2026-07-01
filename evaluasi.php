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

$page_title = "Evaluasi Kegiatan";

// Fetch kegiatan list for form
$kegiatan_stmt = $pdo->query("SELECT id, nama_kegiatan, tahun FROM kegiatan ORDER BY tahun DESC, id DESC");
$list_kegiatan = $kegiatan_stmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $kegiatan_id = $_POST['kegiatan_id'];
    $skor_rating = $_POST['skor_rating'];
    $keberhasilan = $_POST['keberhasilan'];
    $kekurangan = $_POST['kekurangan'];
    $saran = $_POST['saran'];
    $user_id = $_SESSION['user_id'];
    
    $insert = $pdo->prepare("INSERT INTO evaluasi (kegiatan_id, user_id, skor_rating, keberhasilan, kekurangan, saran) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->execute([$kegiatan_id, $user_id, $skor_rating, $keberhasilan, $kekurangan, $saran]);
    
    // Update status kegiatan menjadi "Selesai" jika dievaluasi
    $update_kegiatan = $pdo->prepare("UPDATE kegiatan SET status = 'Selesai' WHERE id = ?");
    $update_kegiatan->execute([$kegiatan_id]);

    header("Location: evaluasi.php?msg=success");
    exit();
}

// Fetch Evaluasi Data
$eval_stmt = $pdo->query("SELECT e.*, k.nama_kegiatan, k.tahun, k.jenis_kegiatan, u.nama_lengkap 
                         FROM evaluasi e 
                         JOIN kegiatan k ON e.kegiatan_id = k.id 
                         JOIN users u ON e.user_id = u.id
                         ORDER BY e.id DESC");
$evaluasi_list = $eval_stmt->fetchAll();
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
         .modal-content { background-color: var(--surface-color); padding: 2rem; border-radius: var(--radius-lg); width: 100%; max-width: 600px; position: relative; max-height: 90vh; overflow-y: auto;}
         .close-btn { position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
         .rating-stars { display: flex; gap: 5px; color: #cbd5e1; font-size: 1.5rem; cursor: pointer; flex-direction: row-reverse; justify-content: flex-end; }
         .rating-stars input { display: none; }
         .rating-stars label:hover, .rating-stars label:hover ~ label, .rating-stars input:checked ~ label { color: #f59e0b; }
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
                        Laporan Evaluasi berhasil disimpan. Status kegiatan diubah menjadi Selesai.
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <h2 class="card-title" style="margin: 0;">Rekapitulasi Evaluasi Kegiatan</h2>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn" onclick="window.print()" style="background: #10b981; color: white; border: none;">
                                    <i class='bx bx-printer'></i> Export PDF
                                </button>
                                <?php if($_SESSION['role_id'] == 3): // Hanya Panitia yang buat evaluasi ?>
                                <button class="btn btn-primary" onclick="document.getElementById('modalTambah').classList.add('active')" style="width: auto;">
                                    <i class='bx bx-edit-alt'></i> Buat Evaluasi
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kegiatan & Kategori</th>
                                    <th>Penilai</th>
                                    <th>Rating (Skala 5)</th>
                                    <th>Keberhasilan Utama</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($evaluasi_list) > 0): ?>
                                    <?php foreach ($evaluasi_list as $ev): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--primary-color);"><?= htmlspecialchars($ev['nama_kegiatan']) ?></strong><br>
                                            <span style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($ev['jenis_kegiatan']) ?> (<?= htmlspecialchars($ev['tahun']) ?>)</span>
                                        </td>
                                        <td><?= htmlspecialchars($ev['nama_lengkap']) ?></td>
                                        <td>
                                            <div style="color: #f59e0b; display: flex; gap: 2px; font-size: 1.2rem;">
                                                <?php for($i=1; $i<=5; $i++) { echo $i <= $ev['skor_rating'] ? "<i class='bx bxs-star'></i>" : "<i class='bx bx-star'></i>"; } ?>
                                            </div>
                                        </td>
                                        <td>
                                            <p style="font-size: 0.9rem; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?= htmlspecialchars($ev['keberhasilan']) ?>
                                            </p>
                                        </td>
                                        <td>
                                            <a href="kegiatan_detail.php?id=<?= $ev['kegiatan_id'] ?>" style="color: var(--primary-color);"><i class='bx bx-file'></i> Detail</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">Belum ada hasil evaluasi yang diinput.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form Evaluasi -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('modalTambah').classList.remove('active')">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Input Laporan Evaluasi</h2>
            
            <form action="evaluasi.php" method="POST">
                <input type="hidden" name="action" value="save">
                
                <div class="form-group">
                    <label>Pilih Kegiatan yang Dievaluasi</label>
                    <select name="kegiatan_id" class="form-control" required style="appearance: auto;">
                        <option value="">-- Pilih Kegiatan --</option>
                        <?php foreach($list_kegiatan as $keg): ?>
                            <option value="<?= $keg['id'] ?>"><?= htmlspecialchars($keg['nama_kegiatan'] . " (" . $keg['tahun'] . ")") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Rating Keberhasilan Keseluruhan</label>
                    <div class="rating-stars">
                        <input type="radio" id="star5" name="skor_rating" value="5" required> <label for="star5"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star4" name="skor_rating" value="4"> <label for="star4"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star3" name="skor_rating" value="3"> <label for="star3"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star2" name="skor_rating" value="2"> <label for="star2"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star1" name="skor_rating" value="1"> <label for="star1"><i class='bx bxs-star'></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Poin Keberhasilan</label>
                    <textarea name="keberhasilan" class="form-control" rows="3" required placeholder="Apa saja yang berjalan sangat baik?"></textarea>
                </div>

                <div class="form-group">
                    <label>Kekurangan / Area Peningkatan</label>
                    <textarea name="kekurangan" class="form-control" rows="3" required placeholder="Apa yang masih kurang memuaskan?"></textarea>
                </div>

                <div class="form-group">
                    <label>Saran Untuk Kegiatan Serupa Selanjutnya</label>
                    <textarea name="saran" class="form-control" rows="2" required placeholder="Kesimpulan akhir dan saran konkrit..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" class="btn" style="background: #e2e8f0; color: #1e293b;" onclick="document.getElementById('modalTambah').classList.remove('active')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Kirim Evaluasi</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
