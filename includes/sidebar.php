<?php
$role_id = $_SESSION['role_id'] ?? 4;
$current_page = basename($_SERVER['PHP_SELF']);

// Pemetaan menu per peran (role) untuk struktur navigasi terorganisir
$sections = [];

if ($role_id == 1) { // Admin Sistem (IT Admin)
    $sections = [
        'Menu Utama' => [
            ['file' => 'dashboard.php', 'label' => 'Beranda', 'icon' => 'bx-home-alt', 'active_pages' => ['dashboard.php']],
            ['file' => 'pencarian.php', 'label' => 'Cari Pengetahuan', 'icon' => 'bx-search-alt-2', 'active_pages' => ['pencarian.php', 'search_results.php']],
            ['file' => 'forum.php', 'label' => 'Forum Diskusi', 'icon' => 'bx-conversation', 'active_pages' => ['forum.php', 'forum_detail.php']]
        ],
        'Sistem & Keamanan' => [
            ['file' => 'users.php', 'label' => 'Manajemen Pengguna', 'icon' => 'bx-group', 'active_pages' => ['users.php']],
            ['file' => 'akses.php', 'label' => 'Matriks Hak Akses', 'icon' => 'bx-key', 'active_pages' => ['akses.php']],
            ['file' => 'backup.php', 'label' => 'Backup Database', 'icon' => 'bx-data', 'active_pages' => ['backup.php']]
        ]
    ];
} elseif ($role_id == 2) { // Manajemen (Pelaksana)
    $sections = [
        'Menu Utama' => [
            ['file' => 'dashboard.php', 'label' => 'Beranda', 'icon' => 'bx-home-alt', 'active_pages' => ['dashboard.php']],
            ['file' => 'pencarian.php', 'label' => 'Cari Pengetahuan', 'icon' => 'bx-search-alt-2', 'active_pages' => ['pencarian.php', 'search_results.php']]
        ],
        'Operasional Kegiatan' => [
            ['file' => 'kegiatan.php', 'label' => 'Manajemen Kegiatan', 'icon' => 'bx-calendar-event', 'active_pages' => ['kegiatan.php', 'kegiatan_detail.php']],
            ['file' => 'knowledge.php', 'label' => 'Repository Dokumen', 'icon' => 'bx-folder-open', 'active_pages' => ['knowledge.php']],
            ['file' => 'lesson_learned.php', 'label' => 'Lesson Learned', 'icon' => 'bx-bulb', 'active_pages' => ['lesson_learned.php']],
            ['file' => 'templates.php', 'label' => 'Template Dokumen', 'icon' => 'bx-copy-alt', 'active_pages' => ['templates.php', 'template.php']]
        ],
        'Komunikasi' => [
            ['file' => 'forum.php', 'label' => 'Forum Diskusi', 'icon' => 'bx-conversation', 'active_pages' => ['forum.php', 'forum_detail.php']]
        ]
    ];
} elseif ($role_id == 3) { // Panitia (Evaluator)
    $sections = [
        'Menu Utama' => [
            ['file' => 'dashboard.php', 'label' => 'Beranda', 'icon' => 'bx-home-alt', 'active_pages' => ['dashboard.php']],
            ['file' => 'pencarian.php', 'label' => 'Cari Pengetahuan', 'icon' => 'bx-search-alt-2', 'active_pages' => ['pencarian.php', 'search_results.php']]
        ],
        'Operasional Kegiatan' => [
            ['file' => 'kegiatan.php', 'label' => 'Manajemen Kegiatan', 'icon' => 'bx-calendar-event', 'active_pages' => ['kegiatan.php', 'kegiatan_detail.php']],
            ['file' => 'knowledge.php', 'label' => 'Repository Dokumen', 'icon' => 'bx-folder-open', 'active_pages' => ['knowledge.php']],
            ['file' => 'lesson_learned.php', 'label' => 'Lesson Learned', 'icon' => 'bx-bulb', 'active_pages' => ['lesson_learned.php']]
        ],
        'Quality Control' => [
            ['file' => 'evaluasi.php', 'label' => 'Input Evaluasi & Saran', 'icon' => 'bx-check-shield', 'active_pages' => ['evaluasi.php']],
            ['file' => 'templates.php', 'label' => 'Template Dokumen', 'icon' => 'bx-copy-alt', 'active_pages' => ['templates.php', 'template.php']]
        ],
        'Komunikasi' => [
            ['file' => 'forum.php', 'label' => 'Forum Diskusi', 'icon' => 'bx-conversation', 'active_pages' => ['forum.php', 'forum_detail.php']]
        ]
    ];
} elseif ($role_id == 4) { // Yayasan (Decision Maker)
    $sections = [
        'Menu Utama' => [
            ['file' => 'dashboard.php', 'label' => 'Beranda', 'icon' => 'bx-home-alt', 'active_pages' => ['dashboard.php']],
            ['file' => 'pencarian.php', 'label' => 'Cari Pengetahuan', 'icon' => 'bx-search-alt-2', 'active_pages' => ['pencarian.php', 'search_results.php']]
        ],
        'Monitoring & Yayasan' => [
            ['file' => 'knowledge.php', 'label' => 'Cari Referensi', 'icon' => 'bx-search-alt', 'active_pages' => ['knowledge.php']],
            ['file' => 'evaluasi.php', 'label' => 'Lihat Evaluasi', 'icon' => 'bx-file-find', 'active_pages' => ['evaluasi.php']],
            ['file' => 'laporan.php', 'label' => 'Laporan & Insight', 'icon' => 'bx-bar-chart-alt-2', 'active_pages' => ['laporan.php']]
        ],
        'Komunikasi' => [
            ['file' => 'forum.php', 'label' => 'Forum Diskusi', 'icon' => 'bx-conversation', 'active_pages' => ['forum.php', 'forum_detail.php']]
        ]
    ];
} else {
    // Fallback default
    $sections = [
        'Menu Utama' => [
            ['file' => 'dashboard.php', 'label' => 'Beranda', 'icon' => 'bx-home-alt', 'active_pages' => ['dashboard.php']]
        ]
    ];
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class='bx bxs-school'></i>
        <div class="brand-info">
            <span class="brand-title">KMS Sanmar</span>
            <span class="brand-subtitle">SMA Santa Maria</span>
        </div>
    </div>
    
    <nav class="sidebar-menu">
        <?php foreach ($sections as $section_label => $items): ?>
            <?php 
            // Filter items by dynamic database permission matrix
            $allowed_items = array_filter($items, function($item) {
                return has_permission($item['file']);
            });
            
            // If no permitted items in this section, hide the entire section
            if (empty($allowed_items)) {
                continue;
            }
            ?>
            <div class="menu-label"><?= htmlspecialchars($section_label) ?></div>
            <?php foreach ($allowed_items as $item): ?>
                <?php 
                $is_active = in_array($current_page, $item['active_pages']);
                $active_class = $is_active ? 'active' : '';
                ?>
                <a href="<?= htmlspecialchars($item['file']) ?>" class="menu-item <?= $active_class ?>">
                    <i class='bx <?= htmlspecialchars($item['icon']) ?>'></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="menu-item menu-logout">
            <i class='bx bx-log-out'></i>
            <span>Keluar Aplikasi</span>
        </a>
        <div class="sidebar-profile">
            <div class="profile-avatar">
                <?php
                $nama = $_SESSION['nama_lengkap'] ?? 'User';
                $words = explode(" ", $nama);
                $initials = "";
                if (count($words) >= 2) {
                    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                } else {
                    $initials = strtoupper(substr($nama, 0, 2));
                }
                echo htmlspecialchars($initials);
                ?>
            </div>
            <div class="profile-info">
                <div class="profile-name" title="<?= htmlspecialchars($nama) ?>"><?= htmlspecialchars($nama) ?></div>
                <div class="profile-role">
                    <?php 
                    if ($role_id == 1) echo 'Admin Sistem';
                    elseif ($role_id == 2) echo 'Manajemen';
                    elseif ($role_id == 3) echo 'Panitia';
                    elseif ($role_id == 4) echo 'Yayasan';
                    else echo 'Pengguna';
                    ?>
                </div>
            </div>
        </div>
    </div>
</aside>
