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

$page_title = "Knowledge Repository";

// Ambil daftar kegiatan untuk dropdown filter & input
$kegiatan_stmt = $pdo->query("SELECT id, nama_kegiatan, tahun FROM kegiatan ORDER BY tahun DESC, id DESC");
$list_kegiatan = $kegiatan_stmt->fetchAll();

// Fetch unique document types for dropdown option list
$default_types = ["Proposal", "Laporan", "SOP", "Rundown", "Lainnya"];
$db_types_stmt = $pdo->query("SELECT DISTINCT tipe_dokumen FROM knowledge_docs WHERE tipe_dokumen != ''");
$db_types = $db_types_stmt->fetchAll(PDO::FETCH_COLUMN);
$all_types = array_unique(array_merge($default_types, $db_types));
sort($all_types);

// Jika ada aksi upload dokumen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $kegiatan_id = $_POST['kegiatan_id'];
    $judul = $_POST['judul'];
    $tipe_dokumen = $_POST['tipe_dokumen'];
    if ($tipe_dokumen === '__BARU__' && !empty($_POST['tipe_dokumen_baru'])) {
        $tipe_dokumen = trim($_POST['tipe_dokumen_baru']);
    }
    $uploader_id = $_SESSION['user_id'];
    
    // Setup File Upload Logic
    $upload_dir = 'uploads/docs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_path = '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file_info = pathinfo($_FILES['file']['name']);
        // Sanitasi nama file dan hindari duplicate
        $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file_info['basename']);
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
            $file_path = $destination;
        }
    }
    
    // Cek max version document sebelumnya (jika ada file dengan judul yg mirip)
    // Di sini kita asumsikan versi awal 1
    $versi = 1;

    $insert = $pdo->prepare("INSERT INTO knowledge_docs (kegiatan_id, judul, tipe_dokumen, file_path, versi, uploader_id) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->execute([$kegiatan_id, $judul, $tipe_dokumen, $file_path, $versi, $uploader_id]);
    
    header("Location: knowledge.php?msg=success");
    exit();
}

// Ambil data Repository Documents dengan filter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT d.*, k.nama_kegiatan, k.tahun, u.nama_lengkap as uploader_name 
         FROM knowledge_docs d 
         LEFT JOIN kegiatan k ON d.kegiatan_id = k.id 
         LEFT JOIN users u ON d.uploader_id = u.id";

$params = [];
if ($search) {
    $sql .= " WHERE d.judul LIKE ? OR k.nama_kegiatan LIKE ? OR k.tahun LIKE ?";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY d.upload_date DESC";
$doc_stmt = $pdo->prepare($sql);
$doc_stmt->execute($params);
$documents = $doc_stmt->fetchAll();
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
         .modal-content { background-color: var(--surface-color); padding: 2rem; border-radius: var(--radius-lg); width: 100%; max-width: 500px; position: relative; }
         .close-btn { position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
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
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                    <div class="alert badge-success" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #10b981; border-radius: var(--radius-md);">
                        Dokumen berhasil diunggah ke repository!
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Dokumen Terpusat (Explicit Knowledge)</h2>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <!-- Search Bar -->
                            <form action="knowledge.php" method="GET" style="display: flex; gap: 5px; width: 250px;">
                                <div style="position: relative; width: 100%;">
                                    <i class='bx bx-search' style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                                    <input type="text" name="search" placeholder="Cari dokumen..." value="<?= htmlspecialchars($search) ?>" 
                                           style="width: 100%; padding: 8px 12px 8px 35px; border: 1px solid #e2e8f0; border-radius: var(--radius-md); font-size: 0.8rem;">
                                </div>
                            </form>

                            <?php if($_SESSION['role_id'] == 2): // Hanya Manajemen yang bisa upload dokumen ?>
                            <button class="btn btn-primary" onclick="document.getElementById('modalUpload').classList.add('active')" style="width: auto; padding: 8px 15px;">
                                <i class='bx bx-upload'></i> Upload Dokumen
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Dokumen & Versi</th>
                                    <th>Kategori</th>
                                    <th>Terkait Kegiatan</th>
                                    <th>Pengunggah & Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($documents) > 0): ?>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class='bx bxs-file-pdf' style="color: #ef4444; font-size: 1.5rem;"></i>
                                                <div>
                                                    <span style="font-weight: 500;"><?= htmlspecialchars($doc['judul']) ?></span>
                                                    <span class="badge" style="background: #e2e8f0; color: #475569; font-size: 0.65rem; margin-left: 4px;">v<?= $doc['versi'] ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-primary"><?= htmlspecialchars($doc['tipe_dokumen']) ?></span></td>
                                        <td>
                                            <?= htmlspecialchars($doc['nama_kegiatan']) ?><br>
                                            <small style="color: var(--text-secondary);">Th: <?= htmlspecialchars($doc['tahun']) ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($doc['uploader_name']) ?><br>
                                            <small style="color: var(--text-secondary);"><?= date('d M Y, H:i', strtotime($doc['upload_date'])) ?></small>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 10px;">
                                                <?php if($doc['file_path'] && $doc['file_path'] != ''): ?>
                                                    <a href="<?= $doc['file_path'] ?>" target="_blank" title="Preview" style="color: var(--primary-color); font-size: 1.2rem;"><i class='bx bx-show'></i></a>
                                                    <a href="<?= $doc['file_path'] ?>" download title="Download" style="color: var(--secondary-color); font-size: 1.2rem;"><i class='bx bx-download'></i></a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0)" onclick="alert('File fisik belum diunggah untuk dokumen ini.')" title="File tidak tersedia" style="color: #cbd5e1; font-size: 1.2rem; cursor: not-allowed;"><i class='bx bx-show'></i></a>
                                                    <a href="javascript:void(0)" onclick="alert('File fisik belum diunggah untuk dokumen ini.')" title="File tidak tersedia" style="color: #cbd5e1; font-size: 1.2rem; cursor: not-allowed;"><i class='bx bx-download'></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">Belum ada dokumen yang diunggah di repository.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form Upload -->
    <div id="modalUpload" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('modalUpload').classList.remove('active')">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Upload Dokumen Pengetahuan</h2>
            
            <form action="knowledge.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                
                <div class="form-group">
                    <label>Judul/Nama Dokumen</label>
                    <input type="text" name="judul" class="form-control" required placeholder="Contoh: Proposal Final Studi Wisata">
                </div>
                
                <div class="form-group">
                    <label>Tipe Dokumen (Kategori)</label>
                    <select name="tipe_dokumen" class="form-control" required style="appearance: auto;">
                        <option value="">-- Pilih Tipe --</option>
                        <?php foreach ($all_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                        <option value="__BARU__">+ Tipe Dokumen Baru...</option>
                    </select>
                    <div id="tipe_baru_wrapper" style="display: none; margin-top: 10px;">
                        <label style="font-weight: 500; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nama Tipe Dokumen Baru</label>
                        <input type="text" name="tipe_dokumen_baru" id="tipe_dokumen_baru" class="form-control" placeholder="Masukkan tipe dokumen baru">
                    </div>
                </div>

                <div class="form-group">
                    <label>Tautkan Ke Kegiatan (Konteks Event)</label>
                    <select name="kegiatan_id" class="form-control" required style="appearance: auto;">
                        <option value="">-- Pilih Kegiatan --</option>
                        <?php foreach($list_kegiatan as $keg): ?>
                            <option value="<?= $keg['id'] ?>"><?= htmlspecialchars($keg['nama_kegiatan'] . " (" . $keg['tahun'] . ")") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>File Upload (PDF/DOCX/Excel)</label>
                    <input type="file" name="file" class="form-control" required style="padding: 0.5rem;" accept=".pdf,.doc,.docx,.xls,.xlsx">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" class="btn" style="background: #e2e8f0; color: #1e293b;" onclick="document.getElementById('modalUpload').classList.remove('active')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><i class='bx bx-cloud-upload'></i> Unggah & Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('modalUpload');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }

        // Toggle input for new document type
        document.querySelector('select[name="tipe_dokumen"]').addEventListener('change', function() {
            var wrapper = document.getElementById('tipe_baru_wrapper');
            var input = document.getElementById('tipe_dokumen_baru');
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
