<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Proteksi: hanya guru_mapel dan wali_kelas yang boleh akses
$allowed_roles = ['guru_mapel', 'wali_kelas'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';

$guru_id = $_SESSION['id'];
$guru_nama = $_SESSION['nama'] ?? 'Guru';

// Ambil semua informasi untuk guru
$sql_informasi = "
    SELECT * FROM informasi 
    WHERE (ditujukan = 'guru' OR ditujukan = 'umum')
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
        .notification-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .notification-new {
            border-left: 4px solid #f68c2e;
            background: #fff8f0;
        }
        .notification-badge {
            background: linear-gradient(135deg, #f68c2e 0%, #ff9d4d 100%);
        }
        .badge-umum { background: #4d6651; }
        .badge-guru { background: #2196F3; }
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
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($guru_nama) ?>
                </span>
                <a href="dashboard_guru.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-0">
                                    <i class="fas fa-bullhorn me-2"></i>Informasi & Pengumuman
                                </h4>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($informasi_baru > 0): ?>
                                    <span class="badge notification-badge fs-6">
                                        <i class="fas fa-star me-1"></i><?= $informasi_baru ?> Informasi Baru
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Notifikasi -->
        <div class="row">
            <div class="col-12">
                <?php if (count($informasi_list) > 0): ?>
                    <?php foreach ($informasi_list as $info): 
                        $is_baru = (strtotime($info['tanggal']) > (time() - 7*24*60*60));
                        $badge_class = $info['ditujukan'] == 'umum' ? 'badge-umum' : 'badge-guru';
                        $icon = $info['ditujukan'] == 'umum' ? 'fas fa-users' : 'fas fa-chalkboard-teacher';
                    ?>
                        <div class="card notification-card <?= $is_baru ? 'notification-new' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <h5 class="card-title mb-0 me-2"><?= htmlspecialchars($info['judul']) ?></h5>
                                        <?php if ($is_baru): ?>
                                            <span class="badge bg-danger me-2">
                                                <i class="fas fa-circle me-1"></i>BARU
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <i class="<?= $icon ?> me-1"></i>
                                            <?= $info['ditujukan'] == 'umum' ? 'Semua' : 'Guru Saja' ?>
                                        </span>
                                    </div>
                                    <small class="notification-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($info['tanggal'])) ?>
                                    </small>
                                </div>
                                
                                <p class="card-text"><?= nl2br(htmlspecialchars($info['isi'])) ?></p>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Diterbitkan: <?= date('l, d F Y', strtotime($info['tanggal'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <h3>Tidak Ada Informasi</h3>
                                <p class="mb-0">Belum ada informasi atau pengumuman yang tersedia.</p>
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
                            Informasi terbaru akan muncul di sini. Total <?= count($informasi_list) ?> informasi ditemukan.
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
            }
        });

        // Mark as read animation
        document.querySelectorAll('.notification-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.backgroundColor = '#f8f9fa';
                setTimeout(() => {
                    this.style.backgroundColor = '';
                }, 500);
            });
        });
    </script>
</body>
</html>
