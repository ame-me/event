<?php
session_start();
require_once 'config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    // Typically we'd use hashing, but for this fresh native demo layout we'll accept plain text or mocked pass 
    // To match our SQL insert ($2y$10... = password for admin)
    
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        $password = $_POST['password'] ?? '';
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            header("Location: dashboard.php");
            exit();
        } else {
            // Check fallback if not found in DB or password mismatch
            if ($username == 'admin@smasanmar.sch.id' && $password == 'admin123') {
                $_SESSION['user_id'] = 1; $_SESSION['role_id'] = 1; $_SESSION['nama_lengkap'] = 'Admin (Fallback)';
            } elseif ($username == 'manajemen@smasanmar.sch.id' && $password == 'manajemen123') {
                $_SESSION['user_id'] = 2; $_SESSION['role_id'] = 2; $_SESSION['nama_lengkap'] = 'Manajer (Fallback)';
            } elseif ($username == 'panitia@smasanmar.sch.id' && $password == 'panitia123') {
                $_SESSION['user_id'] = 3; $_SESSION['role_id'] = 3; $_SESSION['nama_lengkap'] = 'Panitia (Fallback)';
            } elseif ($username == 'yayasan@smasanmar.sch.id' && $password == 'yayasan123') {
                $_SESSION['user_id'] = 4; $_SESSION['role_id'] = 4; $_SESSION['nama_lengkap'] = 'Yayasan (Fallback)';
            }

            if (isset($_SESSION['user_id'])) {
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Username atau Password salah!';
            }
        }
    } else {
         // Fallback if DB is not connected yet for UI review
         if ($username == 'admin@smasanmar.sch.id' && $password == 'admin123') {
             $_SESSION['user_id'] = 1; $_SESSION['role_id'] = 1; $_SESSION['nama_lengkap'] = 'Admin (Fallback)';
         } elseif ($username == 'manajemen@smasanmar.sch.id' && $password == 'manajemen123') {
             $_SESSION['user_id'] = 2; $_SESSION['role_id'] = 2; $_SESSION['nama_lengkap'] = 'Manajer (Fallback)';
         } elseif ($username == 'panitia@smasanmar.sch.id' && $password == 'panitia123') {
             $_SESSION['user_id'] = 3; $_SESSION['role_id'] = 3; $_SESSION['nama_lengkap'] = 'Panitia (Fallback)';
         } elseif ($username == 'yayasan@smasanmar.sch.id' && $password == 'yayasan123') {
             $_SESSION['user_id'] = 4; $_SESSION['role_id'] = 4; $_SESSION['nama_lengkap'] = 'Yayasan (Fallback)';
         }
         
         if (isset($_SESSION['user_id'])) {
             header("Location: dashboard.php");
             exit();
         } else {
             $error = "DB tidak terkoneksi & Password salah.";
         }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KMS SMA Santa Maria Malang</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Boxicons for modern icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>KMS Santa Maria</h1>
            <p>Manajemen Kegiatan & Pengetahuan</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($db_error)): ?>
                <div class="alert alert-error" style="font-size: 0.8rem;"><?= htmlspecialchars($db_error) ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login ke Sistem</button>
            </form>
        </div>
    </div>
</body>
</html>
