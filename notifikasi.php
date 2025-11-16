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
    <title>Informasi - SDIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
        }

        /* Top Header */
        .top-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            color: white;
        }

        .top-header .header-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }

        .header-info h4 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .header-info p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .header-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            display: block;
        }

        .stat-label {
            font-size: 11px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Pills */
        .filter-container {
            max-width: 800px;
            margin: -20px auto 0;
            padding: 0 20px 20px;
            position: relative;
            z-index: 10;
        }

        .filter-pills {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 15px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .filter-pills::-webkit-scrollbar {
            display: none;
        }

        .filter-pill {
            padding: 10px 20px;
            border-radius: 25px;
            background: #f5f5f5;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .filter-pill:hover {
            background: #e0e0e0;
            color: #333;
        }

        .filter-pill.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Main Content */
        .content-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        /* Info Card */
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .info-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .info-card.new::before {
            width: 8px;
            background: linear-gradient(180deg, #f093fb 0%, #f5576c 100%);
            animation: pulse-width 2s infinite;
        }

        @keyframes pulse-width {
            0%, 100% { width: 8px; opacity: 1; }
            50% { width: 12px; opacity: 0.8; }
        }

        .info-card.new {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
            flex: 1;
        }

        .card-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-new {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            animation: badge-pulse 2s infinite;
        }

        @keyframes badge-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .badge-umum {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #006644;
        }

        .badge-siswa {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #0066cc;
        }

        .card-content {
            color: #555;
            font-size: 15px;
            line-height: 1.7;
            margin: 15px 0;
        }

        .card-footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        .card-date {
            font-size: 13px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .card-time {
            font-size: 13px;
            color: #999;
            font-weight: 500;
        }

        /* Date Separator */
        .date-separator {
            text-align: center;
            margin: 30px 0 20px;
            position: relative;
        }

        .date-separator::before,
        .date-separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.3));
        }

        .date-separator::before {
            left: 0;
        }

        .date-separator::after {
            right: 0;
            background: linear-gradient(to left, transparent, rgba(255,255,255,0.3));
        }

        .date-separator span {
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h5 {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            font-size: 15px;
        }

        /* Smooth Animations */
        .info-card {
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-stats {
                flex-direction: column;
                gap: 10px;
            }

            .top-header {
                padding: 15px;
            }

            .filter-container {
                margin-top: -15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="header-content">
            <a href="dashboard_siswa.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            
            <div class="header-info">
                <h4><i class="fas fa-bell me-2"></i>Informasi</h4>
                <p><?= htmlspecialchars($siswa_nama) ?> • <?= htmlspecialchars($nama_kelas) ?></p>
            </div>

            <div class="header-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= count($informasi_list) ?></span>
                    <span class="stat-label">Total</span>
                </div>
                <?php if ($informasi_baru > 0): ?>
                <div class="stat-item">
                    <span class="stat-number"><?= $informasi_baru ?></span>
                    <span class="stat-label">Baru</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="filter-container">
        <div class="filter-pills">
            <a href="notifikasi.php" class="filter-pill <?= !isset($_GET['filter']) ? 'active' : '' ?>">
                <i class="fas fa-th-large me-1"></i> Semua
            </a>
            <a href="notifikasi.php?filter=baru" class="filter-pill <?= isset($_GET['filter']) && $_GET['filter'] == 'baru' ? 'active' : '' ?>">
                <i class="fas fa-star me-1"></i> Baru
            </a>
            <a href="notifikasi.php?filter=umum" class="filter-pill <?= isset($_GET['filter']) && $_GET['filter'] == 'umum' ? 'active' : '' ?>">
                <i class="fas fa-users me-1"></i> Umum
            </a>
            <a href="notifikasi.php?filter=siswa" class="filter-pill <?= isset($_GET['filter']) && $_GET['filter'] == 'siswa' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate me-1"></i> Siswa
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-container">
        <?php if (count($informasi_list) > 0): ?>
            <?php 
            $filter = $_GET['filter'] ?? '';
            $current_date = '';
            $visible_count = 0;
            
            foreach ($informasi_list as $info): 
                $is_baru = (strtotime($info['tanggal']) > (time() - 7*24*60*60));
                $badge_class = $info['ditujukan'] == 'umum' ? 'badge-umum' : 'badge-siswa';
                $badge_text = $info['ditujukan'] == 'umum' ? 'Untuk Semua' : 'Khusus Siswa';
                
                // Apply filter
                if ($filter === 'baru' && !$is_baru) continue;
                if ($filter === 'umum' && $info['ditujukan'] !== 'umum') continue;
                if ($filter === 'siswa' && $info['ditujukan'] !== 'siswa') continue;
                
                $visible_count++;
                
                // Date separator
                $message_date = date('d F Y', strtotime($info['tanggal']));
                if ($current_date !== $message_date) {
                    $current_date = $message_date;
                    if ($visible_count > 1) {
                        echo '<div class="date-separator"><span>' . $message_date . '</span></div>';
                    }
                }
            ?>
                <div class="info-card <?= $is_baru ? 'new' : '' ?>">
                    <div class="card-header-row">
                        <h5 class="card-title"><?= htmlspecialchars($info['judul']) ?></h5>
                    </div>
                    
                    <div class="card-badges">
                        <?php if ($is_baru): ?>
                            <span class="badge-custom badge-new">
                                <i class="fas fa-sparkles me-1"></i>BARU
                            </span>
                        <?php endif; ?>
                        <span class="badge-custom <?= $badge_class ?>">
                            <i class="fas <?= $info['ditujukan'] == 'umum' ? 'fa-users' : 'fa-user-graduate' ?> me-1"></i>
                            <?= $badge_text ?>
                        </span>
                    </div>
                    
                    <div class="card-content">
                        <?= nl2br(htmlspecialchars($info['isi'])) ?>
                    </div>
                    
                    <div class="card-footer-row">
                        <div class="card-date">
                            <i class="fas fa-calendar-alt"></i>
                            <?= date('d M Y', strtotime($info['tanggal'])) ?>
                        </div>
                        <div class="card-time">
                            <i class="fas fa-clock me-1"></i>
                            <?= date('H:i', strtotime($info['tanggal'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($visible_count === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h5>Tidak Ditemukan</h5>
                    <p>Tidak ada informasi yang sesuai dengan filter yang dipilih.</p>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h5>Belum Ada Informasi</h5>
                <p>Saat ini belum ada informasi atau pengumuman yang tersedia.<br>
                Informasi baru akan muncul di sini ketika ada pengumuman penting.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.info-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Scroll to new messages
            const newCards = document.querySelectorAll('.info-card.new');
            if (newCards.length > 0) {
                setTimeout(() => {
                    newCards[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }

            // Card click animation
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>
