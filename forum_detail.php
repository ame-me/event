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

$page_title = "Detail Diskusi";
$diskusi_id = $_GET['id'] ?? 0;

// Validate thread
$thread_stmt = $pdo->prepare("SELECT f.*, u.nama_lengkap, k.nama_kegiatan 
                              FROM forum_diskusi f
                              LEFT JOIN users u ON f.user_id = u.id
                              LEFT JOIN kegiatan k ON f.kegiatan_id = k.id
                              WHERE f.id = ?");
$thread_stmt->execute([$diskusi_id]);
$thread = $thread_stmt->fetch();

if (!$thread) {
    header("Location: forum.php");
    exit();
}

// Handle new reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reply') {
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    
    $insert_comment = $pdo->prepare("INSERT INTO forum_comments (diskusi_id, user_id, content) VALUES (?, ?, ?)");
    $insert_comment->execute([$diskusi_id, $user_id, $content]);
    
    header("Location: forum_detail.php?id=" . $diskusi_id . "#latest");
    exit();
}

// Handle upvote
if (isset($_GET['upvote'])) {
    $comment_id = $_GET['upvote'];
    $stmt = $pdo->prepare("UPDATE forum_comments SET likes = likes + 1 WHERE id = ?");
    $stmt->execute([$comment_id]);
    header("Location: forum_detail.php?id=" . $diskusi_id . "#comment-" . $comment_id);
    exit();
}

// Fetch comments
$comment_stmt = $pdo->prepare("SELECT c.*, u.nama_lengkap 
                               FROM forum_comments c
                               JOIN users u ON c.user_id = u.id
                               WHERE c.diskusi_id = ?
                               ORDER BY c.created_at ASC");
$comment_stmt->execute([$diskusi_id]);
$comments = $comment_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($thread['judul']) ?> - KMS SMA Santa Maria</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
         .thread-header { background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 2rem; border-top: 4px solid var(--primary-color); }
         .thread-header h1 { font-size: 1.5rem; color: var(--text-primary); margin-bottom: 0.5rem; }
         .thread-meta { display: flex; gap: 15px; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
         
         .comment-list { display: flex; flex-direction: column; gap: 1.5rem; }
         .comment-card { display: flex; gap: 15px; background: white; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
         .comment-avatar { width: 45px; height: 45px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; flex-shrink: 0; }
         .comment-body { flex: 1; }
         .comment-author { font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
         .comment-time { font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 10px; display: block; }
         .comment-text { color: #334155; line-height: 1.6; font-size: 0.95rem; white-space: pre-line; }
         
         .reply-box { margin-top: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); }
         .reply-box h3 { margin-bottom: 1rem; font-size: 1.1rem; color: var(--text-primary); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="page-title"><a href="forum.php" style="color:var(--text-secondary); text-decoration:none;"><i class='bx bx-arrow-back'></i> Kembali</a> &nbsp;|&nbsp; <?= $page_title ?></div>
                <div class="user-profile">
                    <span style="font-weight: 500;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                </div>
            </header>
            
            <div class="content-body" style="max-width: 900px; margin: 0 auto;">
                
                <div class="thread-header">
                    <h1><?= htmlspecialchars($thread['judul']) ?></h1>
                    <div class="thread-meta">
                        <span><i class='bx bx-user'></i> Dibuat oleh: <?= htmlspecialchars($thread['nama_lengkap']) ?></span>
                        <span><i class='bx bx-time'></i> <?= date('d M Y, H:i', strtotime($thread['created_at'])) ?></span>
                        <?php if($thread['nama_kegiatan']): ?>
                            <span class="badge" style="background:#e0e7ff; color:var(--primary-color);">Konteks Event: <?= htmlspecialchars($thread['nama_kegiatan']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="comment-list">
                    <?php foreach($comments as $index => $c): ?>
                        <div class="comment-card" <?= ($index === count($comments) - 1) ? 'id="latest"' : '' ?>>
                            <div class="comment-avatar">
                                <?= substr($c['nama_lengkap'], 0, 1) ?>
                            </div>
                            <div class="comment-body">
                                <div class="comment-author"><?= htmlspecialchars($c['nama_lengkap']) ?> <?= $index === 0 ? '<span class="badge" style="background:#10b981; color:white; font-size:0.6rem; margin-left:5px;">Penanya</span>' : '' ?></div>
                                <span class="comment-time"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></span>
                                <div class="comment-text"><?= htmlspecialchars($c['content']) ?></div>
                                <?php if($index > 0): ?>
                                    <div style="margin-top:10px;">
                                        <a href="forum_detail.php?id=<?= $diskusi_id ?>&upvote=<?= $c['id'] ?>" class="btn" style="padding: 2px 8px; font-size: 0.8rem; background: #e2e8f0; color: #475569; text-decoration: none; display: inline-block;">
                                            <i class='bx bx-upvote'></i> Upvote (<?= $c['likes'] ?>)
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="reply-box">
                    <h3>Beri Tanggapan / Jawaban</h3>
                    <form action="forum_detail.php?id=<?= $diskusi_id ?>" method="POST">
                        <input type="hidden" name="action" value="reply">
                        <div class="form-group">
                            <textarea name="content" class="form-control" rows="4" required placeholder="Tuliskan komentar atau jawaban Anda di sini..." style="resize: vertical;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: auto;"><i class='bx bx-send'></i> Kirim Balasan</button>
                    </form>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
