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

$page_title = "Manajemen Kegiatan";

// Fetch data from database with filtering and search
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM kegiatan WHERE 1=1";
$params = [];

if ($filter_jenis) {
    $sql .= " AND jenis_kegiatan = ?";
    $params[] = $filter_jenis;
}

if ($search) {
    $sql .= " AND (nama_kegiatan LIKE ? OR jenis_kegiatan LIKE ? OR tahun LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kegiatan_list = $stmt->fetchAll();

// Handle Form Submission for New Kegiatan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $nama_kegiatan = $_POST['nama_kegiatan'];
    $jenis_kegiatan = $_POST['jenis_kegiatan'];
    if ($jenis_kegiatan === '__BARU__' && !empty($_POST['jenis_kegiatan_baru'])) {
        $jenis_kegiatan = trim($_POST['jenis_kegiatan_baru']);
    }
    $tahun = $_POST['tahun'];
    if ($tahun === '__BARU__' && !empty($_POST['tahun_baru'])) {
        $tahun = trim($_POST['tahun_baru']);
    }
    $deskripsi = $_POST['deskripsi'];
    
    $insert = $pdo->prepare("INSERT INTO kegiatan (nama_kegiatan, jenis_kegiatan, tahun, deskripsi, status) VALUES (?, ?, ?, ?, 'Perencanaan')");
    $insert->execute([$nama_kegiatan, $jenis_kegiatan, $tahun, $deskripsi]);
    
    header("Location: kegiatan.php");
    exit();
}

// Fetch unique categories for dropdown option list
$default_cats = ["Lomba Internal", "Lomba Eksternal", "Perpisahan", "Studi Wisata", "Ekstrakurikuler", "Bakti Sosial"];
$db_cats_stmt = $pdo->query("SELECT DISTINCT jenis_kegiatan FROM kegiatan WHERE jenis_kegiatan != ''");
$db_cats = $db_cats_stmt->fetchAll(PDO::FETCH_COLUMN);
$all_cats = array_unique(array_merge($default_cats, $db_cats));

// Fetch unique years for dropdown option list
$default_years = ["2023", "2024", "2025", "2023/2024", "2024/2025", "2025/2026"];
$db_years_stmt = $pdo->query("SELECT DISTINCT tahun FROM kegiatan WHERE tahun != ''");
$db_years = $db_years_stmt->fetchAll(PDO::FETCH_COLUMN);
$all_years = array_unique(array_merge($default_years, $db_years));
rsort($all_years);
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
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background-color: var(--surface-color);
            padding: 2rem;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
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
                    <span><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>
            
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Daftar Seluruh Kegiatan</h2>
                        <?php if($_SESSION['role_id'] == 2): // Hanya Manajemen yang bisa tambah kegiatan ?>
                        <button class="btn btn-primary" onclick="document.getElementById('modalTambah').classList.add('active')" style="width: auto;">
                            + Tambah Kegiatan Baru
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Filter & Search Bar -->
                    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="kegiatan.php" class="badge" style="text-decoration: none; display: inline-block; padding: 8px 16px; font-size: 0.85rem; background: <?= !$filter_jenis ? 'var(--primary-color)' : '#f4f6f9' ?>; color: <?= !$filter_jenis ? 'white' : 'var(--text-secondary)' ?>;">Semua</a>
                            
                            <?php
                            $cat_stmt = $pdo->query("SELECT DISTINCT jenis_kegiatan FROM kegiatan WHERE jenis_kegiatan != ''");
                            while($cat = $cat_stmt->fetch()):
                                $active = ($filter_jenis == $cat['jenis_kegiatan']);
                            ?>
                            <a href="kegiatan.php?jenis=<?= urlencode($cat['jenis_kegiatan']) ?>" class="badge" 
                               style="text-decoration: none; display: inline-block; padding: 8px 16px; font-size: 0.85rem; background: <?= $active ? 'var(--primary-color)' : '#f4f6f9' ?>; color: <?= $active ? 'white' : 'var(--text-secondary)' ?>;">
                                <?= htmlspecialchars($cat['jenis_kegiatan']) ?>
                            </a>
                            <?php endwhile; ?>
                        </div>

                        <!-- Search Form -->
                        <form action="kegiatan.php" method="GET" style="display: flex; gap: 5px; flex: 1; max-width: 300px;">
                            <?php if($filter_jenis): ?>
                                <input type="hidden" name="jenis" value="<?= htmlspecialchars($filter_jenis) ?>">
                            <?php endif; ?>
                            <div style="position: relative; width: 100%;">
                                <i class='bx bx-search' style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                                <input type="text" name="search" placeholder="Cari kegiatan..." value="<?= htmlspecialchars($search) ?>" 
                                       style="width: 100%; padding: 8px 12px 8px 35px; border: 1px solid #e2e8f0; border-radius: var(--radius-md); font-size: 0.875rem;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="padding: 8px 15px; width: auto;">Cari</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Kegiatan</th>
                                    <th>Kategori</th>
                                    <th>Tahun AJaran</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($kegiatan_list) > 0): ?>
                                    <?php foreach ($kegiatan_list as $row): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?= htmlspecialchars($row['nama_kegiatan']) ?></td>
                                        <td><?= htmlspecialchars($row['jenis_kegiatan']) ?></td>
                                        <td><?= htmlspecialchars($row['tahun']) ?></td>
                                        <td>
                                            <?php 
                                                $badge_class = 'badge-primary'; // Perencanaan
                                                if ($row['status'] == 'Berjalan') $badge_class = 'badge-warning';
                                                if ($row['status'] == 'Selesai') $badge_class = 'badge-success';
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                        </td>
                                        <td>
                                            <a href="kegiatan_detail.php?id=<?= $row['id'] ?>" style="color: var(--primary-color); display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                                <i class='bx bx-search-alt'></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                            Belum ada data kegiatan. Silakan tambah kegiatan baru.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form Tambah Kegiatan -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('modalTambah').classList.remove('active')">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Tambah Kegiatan Baru</h2>
            
            <form action="kegiatan.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Nama/Judul Kegiatan</label>
                    <input type="text" name="nama_kegiatan" class="form-control" required placeholder="Contoh: Lomba Futsal Antar Kelas">
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Kategori Kegiatan</label>
                        <select name="jenis_kegiatan" class="form-control" required style="appearance: auto; background-color: #fff;">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($all_cats as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="__BARU__">+ Kategori Baru...</option>
                        </select>
                        <div id="kategori_baru_wrapper" style="display: none; margin-top: 10px;">
                            <label style="font-weight: 500; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nama Kategori Baru</label>
                            <input type="text" name="jenis_kegiatan_baru" id="jenis_kegiatan_baru" class="form-control" placeholder="Masukkan nama kategori baru">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tahun Ajaran</label>
                        <select name="tahun" class="form-control" required style="appearance: auto; background-color: #fff;">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($all_years as $yr): ?>
                                <option value="<?= htmlspecialchars($yr) ?>"><?= htmlspecialchars($yr) ?></option>
                            <?php endforeach; ?>
                            <option value="__BARU__">+ Tahun Ajaran Baru...</option>
                        </select>
                        <div id="tahun_baru_wrapper" style="display: none; margin-top: 10px;">
                            <label style="font-weight: 500; font-size: 0.85rem; display: block; margin-bottom: 5px;">Tahun Ajaran Baru</label>
                            <input type="text" name="tahun_baru" id="tahun_baru" class="form-control" placeholder="Contoh: 2026/2027">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Deskripsi Singkat</label>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tujuan atau penjelasan singkat..." style="resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" class="btn" style="background: #e2e8f0; color: #1e293b;" onclick="document.getElementById('modalTambah').classList.remove('active')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Kegiatan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('modalTambah');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }

        // Toggle input for new category
        document.querySelector('select[name="jenis_kegiatan"]').addEventListener('change', function() {
            var wrapper = document.getElementById('kategori_baru_wrapper');
            var input = document.getElementById('jenis_kegiatan_baru');
            if (this.value === '__BARU__') {
                wrapper.style.display = 'block';
                input.setAttribute('required', 'required');
                input.focus();
            } else {
                wrapper.style.display = 'none';
                input.removeAttribute('required');
            }
        });

        // Toggle input for new academic year
        document.querySelector('select[name="tahun"]').addEventListener('change', function() {
            var wrapper = document.getElementById('tahun_baru_wrapper');
            var input = document.getElementById('tahun_baru');
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
