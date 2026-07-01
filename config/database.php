<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default XAMPP has no password
$dbname = 'event_kms';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() == 1049) {
        // Database not found, gracefully skip so UI can render
        $db_error = "Database '$dbname' belum dibuat. Pastikan Anda telah mengimpor database.sql di phpMyAdmin.";
    } else {
        $db_error = "Gagal terkoneksi database: " . $e->getMessage();
    }
}

function has_permission($page_name) {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $role_id = $_SESSION['role_id'] ?? null;
    if ($role_id === null) {
        return false;
    }
    
    // Dashboard / Beranda is always allowed for logged-in users
    if ($page_name === 'dashboard.php') {
        return true;
    }
    
    // Clean page name
    $page_name = basename($page_name);
    
    // Map detail pages to their main page permissions
    if ($page_name === 'kegiatan_detail.php') {
        return has_permission('kegiatan.php') || has_permission('evaluasi.php');
    }
    if ($page_name === 'forum_detail.php') $page_name = 'forum.php';
    if ($page_name === 'search_results.php') $page_name = 'pencarian.php';
    if ($page_name === 'template.php') $page_name = 'templates.php';
    
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT is_allowed FROM permissions_matrix WHERE role_id = ? AND page_name = ?");
            $stmt->execute([$role_id, $page_name]);
            $res = $stmt->fetch();
            if ($res) {
                return (bool)$res['is_allowed'];
            }
            return false;
        } catch (PDOException $e) {
            // Fallback to hardcoded rules if table query fails
        }
    }
    
    // Fallback rules matching original sidebar
    if ($role_id == 1) { // Admin IT
        return in_array($page_name, ['users.php', 'backup.php', 'akses.php']);
    } elseif ($role_id == 2) { // Manajemen
        return in_array($page_name, ['pencarian.php', 'kegiatan.php', 'knowledge.php', 'lesson_learned.php', 'templates.php', 'forum.php']);
    } elseif ($role_id == 3) { // Panitia
        return in_array($page_name, ['pencarian.php', 'kegiatan.php', 'knowledge.php', 'lesson_learned.php', 'templates.php', 'evaluasi.php', 'forum.php']);
    } elseif ($role_id == 4) { // Yayasan
        return in_array($page_name, ['pencarian.php', 'knowledge.php', 'evaluasi.php', 'laporan.php', 'forum.php']);
    }
    
    return false;
}

// Override $_SESSION['nama_lengkap'] based on role_id as requested by user
if (isset($_SESSION['role_id'])) {
    $role_id = $_SESSION['role_id'];
    if ($role_id == 1) {
        $_SESSION['nama_lengkap'] = 'Admin IT';
    } elseif ($role_id == 2) {
        $_SESSION['nama_lengkap'] = 'Manajer';
    } elseif ($role_id == 3) {
        $_SESSION['nama_lengkap'] = 'Panitia';
    } elseif ($role_id == 4) {
        $_SESSION['nama_lengkap'] = 'Yayasan';
    }
}
?>
