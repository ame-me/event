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

$page_title = "Forum Diskusi & Tanya Jawab";

// Create new discussion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'new_thread') {
    $kegiatan_id = !empty($_POST['kegiatan_id']) ? $_POST['kegiatan_id'] : null;
    $judul = $_POST['judul'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    
    // Begin Transaction
    $pdo->beginTransaction();
    try {
        $insert_thread = $pdo->prepare("INSERT INTO forum_diskusi (kegiatan_id, judul, user_id, status_aktif) VALUES (?, ?, ?, 1)");
        $insert_thread->execute([$kegiatan_id, $judul, $user_id]);
        $thread_id = $pdo->lastInsertId();
        
        $insert_comment = $pdo->prepare("INSERT INTO forum_comments (diskusi_id, user_id, content, likes) VALUES (?, ?, ?, 0)");
        $insert_comment->execute([$thread_id, $user_id, $content]);
        
        $pdo->commit();
        header("Location: forum.php?msg=thread_created");
    } catch (\Exception $e) {
        $pdo->rollBack();
    }
    exit();
}

// Fetch kegiatan list for form
$kegiatan_stmt = $pdo->query("SELECT id, nama_kegiatan FROM kegiatan ORDER BY id DESC");
$list_kegiatan = $kegiatan_stmt->fetchAll();

// Fetch Threads
$thread_stmt = $pdo->query("SELECT f.*, u.nama_lengkap, k.nama_kegiatan, 
                            (SELECT COUNT(*) FROM forum_comments c WHERE c.diskusi_id = f.id) as reply_count,
                            (SELECT c.content FROM forum_comments c WHERE c.diskusi_id = f.id ORDER BY c.id ASC LIMIT 1) as initial_post
                            FROM forum_diskusi f
                            LEFT JOIN users u ON f.user_id = u.id
                            LEFT JOIN kegiatan k ON f.kegiatan_id = k.id
                            ORDER BY f.id DESC");
$threads = $thread_stmt->fetchAll();
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
         .modal-content { background-color: var(--surface-color); padding: 2rem; border-radius: var(--radius-lg); width: 100%; max-width: 600px; position: relative; }
         .close-btn { position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
         .thread-card { padding: 1.5rem; border-bottom: 1px solid var(--border-color); transition: background 0.2s; }
         .thread-card:last-child { border-bottom: none; }
         .thread-card:hover { background: rgba(13, 71, 161, 0.02); }
         .thread-title { font-size: 1.1rem; font-weight: 600; color: var(--primary-color); margin-bottom: 8px; display: inline-block; text-decoration: none; }
         .thread-title:hover { text-decoration: underline; }
         .thread-snippet { color: #475569; font-size: 0.9rem; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
         .thread-meta { display: flex; gap: 15px; font-size: 0.8rem; color: var(--text-secondary); align-items: center; }
         .reply-badge { background: rgba(245, 158, 11, 0.15); color: #d97706; padding: 4px 10px; border-radius: 999px; font-weight: bold; }
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
                    <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>
            
            <div class="content-body">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h2 style="font-size: 1.5rem; color: var(--text-primary);">Kolaborasi & Diskusi</h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Tempat tanya jawab dan berbagi ide antar panitia/guru.</p>
                    </div>
                    <button class="btn btn-primary" onclick="document.getElementById('modalTambah').classList.add('active')" style="width: auto;">
                        <i class='bx bx-chat'></i> Buat Diskusi Baru
                    </button>
                </div>

                <div class="card" style="padding: 0;">
                    <?php if (count($threads) > 0): ?>
                        <?php foreach ($threads as $th): ?>
                        <div class="thread-card">
                            <a href="forum_detail.php?id=<?= $th['id'] ?>" class="thread-title"><?= htmlspecialchars($th['judul'] ?? '') ?></a>
                            <p class="thread-snippet"><?= htmlspecialchars($th['initial_post'] ?? '') ?></p>
                            
                            <div class="thread-meta">
                                <span><i class='bx bx-user-circle'></i> Oleh: <?= htmlspecialchars($th['nama_lengkap'] ?? '') ?></span>
                                <?php if($th['nama_kegiatan']): ?>
                                    <span><i class='bx bx-calendar-star'></i> Konteks: <?= htmlspecialchars($th['nama_kegiatan']) ?></span>
                                <?php else: ?>
                                    <span><i class='bx bx-hash'></i> Diskusi Umum</span>
                                <?php endif; ?>
                                <span class="reply-badge"><i class='bx bx-message-rounded-dots'></i> <?= max(0, $th['reply_count'] - 1) ?> Balasan</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary);">
                            <i class='bx bx-conversation' style="font-size: 4rem; color: #e2e8f0; margin-bottom: 1rem;"></i>
                            <h3>Belum ada perbincangan.</h3>
                            <p>Jadilah yang pertama memulai diskusi!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form Thread -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('modalTambah').classList.remove('active')">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Buat Diskusi Baru</h2>
            
            <form action="forum.php" method="POST">
                <input type="hidden" name="action" value="new_thread">
                
                <div class="form-group">
                    <label>Judul Diskusi</label>
                    <input type="text" name="judul" class="form-control" required placeholder="Contoh: Bagaimana cara mengurus izin dinas luar?">
                </div>

                <div class="form-group">
                    <label>Terkait Kegiatan (Opsional)</label>
                    <select name="kegiatan_id" class="form-control" style="appearance: auto;">
                        <option value="">-- Diskusi Umum --</option>
                        <?php foreach($list_kegiatan as $keg): ?>
                            <option value="<?= $keg['id'] ?>"><?= htmlspecialchars($keg['nama_kegiatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">Jika pertanyaan ini spesifik untuk sebuah acara, pilih acaranya.</small>
                </div>

                <div class="form-group">
                    <label>Pertanyaan / Topik Pembahasan</label>
                    <textarea name="content" class="form-control" rows="5" required placeholder="Jelaskan apa yang ingin Anda diskusikan..." style="resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" class="btn" style="background: #e2e8f0; color: #1e293b;" onclick="document.getElementById('modalTambah').classList.remove('active')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><i class='bx bx-send'></i> Kirim Topik</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
