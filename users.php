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

$page_title = "Manajemen Pengguna";

// Fetch users with roles
$stmt = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC");
$users = $stmt->fetchAll();

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    $insert = $pdo->prepare("INSERT INTO users (username, password, role_id, nama_lengkap, email) VALUES (?, ?, ?, ?, ?)");
    $insert->execute([$username, $password, $role_id, $nama_lengkap, $email]);
    header("Location: users.php?msg=added");
    exit();
}

// Handle Delete User
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    if ($delete_id === $_SESSION['user_id']) {
        header("Location: users.php?error=self_delete");
        exit();
    }
    
    try {
        $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->execute([$delete_id]);
        header("Location: users.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation
            header("Location: users.php?error=has_relations");
        } else {
            header("Location: users.php?error=delete_failed");
        }
        exit();
    }
}
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
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: var(--radius-lg); width: 100%; max-width: 500px; position: relative; }
        .close-btn { position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div class="page-title"><?= $page_title ?></div>
                <div class="user-profile"><span><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span></div>
            </header>

            <div class="content-body">
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
                    <div class="alert badge-success" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #10b981; border-radius: var(--radius-md);">
                        <i class='bx bx-check-circle' style="font-size: 1.25rem; vertical-align: middle; margin-right: 5px;"></i> Pengguna baru berhasil ditambahkan!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                    <div class="alert badge-success" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #10b981; border-radius: var(--radius-md);">
                        <i class='bx bx-check-circle' style="font-size: 1.25rem; vertical-align: middle; margin-right: 5px;"></i> Pengguna berhasil dihapus!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] == 'self_delete'): ?>
                    <div class="alert alert-error" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #ef4444; border-radius: var(--radius-md);">
                        <i class='bx bx-error-circle' style="font-size: 1.25rem; vertical-align: middle; margin-right: 5px;"></i> Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] == 'has_relations'): ?>
                    <div class="alert alert-error" style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #ef4444; border-radius: var(--radius-md);">
                        <i class='bx bx-error-circle' style="font-size: 1.25rem; vertical-align: middle; margin-right: 5px;"></i> Gagal menghapus pengguna karena pengguna tersebut masih memiliki data terkait (seperti berkas, forum, atau catatan) di dalam sistem.
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Daftar Pengguna Sistem</h2>
                        <button class="btn btn-primary" onclick="document.getElementById('modalUser').classList.add('active')" style="width: auto;">+ Tambah User</button>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($u['role_name']) ?></span></td>
                                    <td>
                                        <a href="users.php?delete_id=<?= $u['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')" style="color: #ef4444;" title="Hapus"><i class='bx bx-trash'></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="modalUser" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('modalUser').classList.remove('active')">&times;</span>
            <h2>Tambah Pengguna Baru</h2>
            <form action="users.php" method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" class="form-control" required style="appearance: auto;">
                        <option value="1">Admin Sistem (IT)</option>
                        <option value="2">Manajemen (Pelaksana)</option>
                        <option value="3">Panitia (Evaluator)</option>
                        <option value="4">Yayasan (Monitoring)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Simpan User</button>
            </form>
        </div>
    </div>
</body>
</html>
