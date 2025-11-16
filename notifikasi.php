<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Proteksi: hanya siswa yang boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';

$siswa_id = $_SESSION['id'];
$siswa_nama = $_SESSION['nama'] ?? 'Siswa';
$siswa_kelas = $_SESSION['kelas'] ?? '';

// Ambil data kelas siswa
$stmt = $conn->prepare("
    SELECT k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.id_kelas = k.id 
    WHERE s.id = ?
");
$stmt->bind_param("i", $siswa_id);
$stmt->execute();
$kelas_result = $stmt->get_result()->fetch_assoc();
$nama_kelas = $kelas_result['nama_kelas'] ?? 'Kelas';
$stmt->close();

// Ambil semua informasi untuk siswa
$sql_informasi = "
    SELECT * FROM informasi 
    WHERE (ditujukan = 'siswa' OR ditujukan = 'umum')
    ORDER BY tanggal DESC";
$informasi_result = $conn->query($sql_informasi);
$informasi_list = $informasi_result ? $informasi_result->fetch_all(MYSQLI_ASSOC) : [];

// Hitung informasi baru (dalam 7 hari terakhir)
$informasi_baru = 0;
foreach ($informasi_list as $info) {
    if (strtotime($info['tanggal']) > (time() - 7*24*60*60)) {
        $informasi_baru++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - SDIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #d9ebd0 0%, #c1dec8 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #f68c2e 0%, #ff9d4d 100%);
            box-shadow: 0 4px 12px rgba(246, 140, 46, 0.3);
        }
        
        .info-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            background: white;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #f68c2e 0%, #ff9d4d 100%);
            color: white;
            border-color: #f68c2e;
        }
        
        .notification-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border-left: 4px solid transparent;
            background: white;
        }
        
        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .notification-new {
            border-left: 4px solid #f68c2e;
            background: #fff8f0;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(246, 140, 46, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(246, 140, 46, 0); }
            100% { box-shadow: 0 0 0 0 rgba(246, 140, 46, 0); }
        }
        
        .notification-badge {
            background: linear-gradient(135deg, #f68c2e 0%, #ff9d4d 100%);
        }
        
        .badge-umum { 
            background: #4d6651;
            color: white;
        }
        
        .badge-siswa { 
            background: #2196F3;
            color: white;
        }
        
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b8e70;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
            color: #6b8e70;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4d6651 0%, #5a7a5e 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-bell me-2"></i>Notifikasi & Informasi
            </span>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-graduate me-1"></i><?= htmlspecialchars($siswa_nama) ?>
                </span>
                <a href="dashboard_siswa.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header Info -->
        <div class="info-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1"><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5>
                    <p class="mb-0 text-muted">Semua informasi dan pengumuman penting akan muncul di sini</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex justify-content-end gap-3">
                        <div class="text-center">
                            <div class="fw-bold text-primary"><?= count($informasi_list) ?></div>
                            <small class="text-muted">Total Info</small>
                        </div>
                        <?php if ($informasi_baru > 0): ?>
                        <div class="text-center">
                            <div class="fw-bold text-success"><?= $informasi_baru ?></div>
                            <small class="text-muted">Info Baru</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <a href="notifikasi.php" class="filter-btn <?= !isset($_GET['filter']) ? 'active' : '' ?>">
                Semua
            </a>
            <a href="notifikasi.php?filter=baru" class="filter-btn <?= isset($_GET['filter']) && $_GET['filter'] == 'baru' ? 'active' : '' ?>">
                <i class="fas fa-star me-1"></i>Baru
            </a>
            <a href="notifikasi.php?filter=umum" class="filter-btn <?= isset($_GET['filter']) && $_GET['filter'] == 'umum' ? 'active' : '' ?>">
                <i class="fas fa-users me-1"></i>Umum
            </a>
            <a href="notifikasi.php?filter=siswa" class="filter-btn <?= isset($_GET['filter']) && $_GET['filter'] == 'siswa' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate me-1"></i>Siswa
            </a>
        </div>

        <!-- Daftar Notifikasi -->
        <div class="row">
            <div class="col-12">
                <?php if (count($informasi_list) > 0): ?>
                    <?php 
                    $filter = $_GET['filter'] ?? '';
                    $visible_count = 0;
                    
                    foreach ($informasi_list as $info): 
                        $is_baru = (strtotime($info['tanggal']) > (time() - 7*24*60*60));
                        $badge_class = $info['ditujukan'] == 'umum' ? 'badge-umum' : 'badge-siswa';
                        $icon = $info['ditujukan'] == 'umum' ? 'fas fa-users' : 'fas fa-user-graduate';
                        
                        // Apply filter
                        if ($filter === 'baru' && !$is_baru) continue;
                        if ($filter === 'umum' && $info['ditujukan'] !== 'umum') continue;
                        if ($filter === 'siswa' && $info['ditujukan'] !== 'siswa') continue;
                        
                        $visible_count++;
                    ?>
                        <div class="card notification-card <?= $is_baru ? 'notification-new' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <h5 class="card-title mb-0 me-2"><?= htmlspecialchars($info['judul']) ?></h5>
                                        <?php if ($is_baru): ?>
                                            <span class="badge bg-danger me-2">
                                                <i class="fas fa-circle me-1"></i>BARU
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge <?= $badge_class ?> me-2">
                                            <i class="<?= $icon ?> me-1"></i>
                                            <?= $info['ditujukan'] == 'umum' ? 'Untuk Semua' : 'Khusus Siswa' ?>
                                        </span>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d M Y', strtotime($info['tanggal'])) ?>
                                        </span>
                                    </div>
                                    <small class="notification-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('H:i', strtotime($info['tanggal'])) ?>
                                    </small>
                                </div>
                                
                                <p class="card-text"><?= nl2br(htmlspecialchars($info['isi'])) ?></p>
                                
                                <div class="mt-3 d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Diterbitkan: <?= date('l, d F Y', strtotime($info['tanggal'])) ?>
                                    </small>
                                    <?php if ($is_baru): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-circle me-1"></i>Baru
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($visible_count === 0): ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h3>Tidak Ditemukan</h3>
                                    <p class="mb-0">Tidak ada informasi yang sesuai dengan filter yang dipilih.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <h3>Belum Ada Informasi</h3>
                                <p class="mb-0">Saat ini belum ada informasi atau pengumuman yang tersedia.</p>
                                <small class="text-muted">Informasi baru akan muncul di sini ketika ada pengumuman penting</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Informasi terbaru akan ditandai dengan warna biru. Total <?= count($informasi_list) ?> informasi tersedia.
                            <?php if ($informasi_baru > 0): ?>
                                <span class="text-success">
                                    <i class="fas fa-star me-1"></i><?= $informasi_baru ?> informasi baru dalam 7 hari terakhir.
                                </span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto scroll to new notifications
        document.addEventListener('DOMContentLoaded', function() {
            const newNotifications = document.querySelectorAll('.notification-new');
            if (newNotifications.length > 0) {
                newNotifications[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Add click event to mark as read (visual only)
                newNotifications.forEach(notification => {
                    notification.addEventListener('click', function() {
                        this.classList.remove('notification-new');
                        this.style.borderLeftColor = '#6c757d';
                    });
                });
            }
            
            // Update active filter button
            const urlParams = new URLSearchParams(window.location.search);
            const filter = urlParams.get('filter');
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                const btnFilter = btn.getAttribute('href').includes('filter=') 
                    ? btn.getAttribute('href').split('filter=')[1]
                    : '';
                if ((!filter && btn.getAttribute('href') === 'notifikasi.php') || 
                    (filter && btnFilter === filter)) {
                    btn.classList.add('active');
                }
            });
        });

        // Smooth animation for notification cards
        document.querySelectorAll('.notification-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html>
