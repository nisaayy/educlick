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
            background: #E4DDD4;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        /* Header Style */
        .chat-header {
            background: #075E54;
            color: white;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        .chat-header .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #25D366;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .chat-header .info {
            flex: 1;
        }

        .chat-header .info h6 {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }

        .chat-header .info small {
            font-size: 12px;
            opacity: 0.8;
        }

        .chat-header .back-btn {
            color: white;
            font-size: 24px;
            text-decoration: none;
            cursor: pointer;
        }

        /* Filter Tabs */
        .filter-tabs {
            background: #075E54;
            padding: 0 16px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .filter-tabs::-webkit-scrollbar {
            display: none;
        }

        .filter-tab {
            padding: 10px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .filter-tab:hover,
        .filter-tab.active {
            color: white;
            border-bottom-color: white;
        }

        /* Chat Container */
        .chat-container {
            height: calc(100vh - 120px);
            overflow-y: auto;
            padding: 12px 8px;
            background: #E4DDD4;
        }

        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 3px;
        }

        /* Message Bubble */
        .message-bubble {
            max-width: 85%;
            margin-bottom: 8px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bubble-content {
            background: white;
            border-radius: 8px;
            padding: 8px 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            position: relative;
        }

        .bubble-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .bubble-title {
            font-size: 14px;
            font-weight: 600;
            color: #075E54;
            flex: 1;
            margin: 0;
        }

        .bubble-badge {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .badge-new {
            background: #FF3B30;
            color: white;
        }

        .badge-umum {
            background: #34C759;
            color: white;
        }

        .badge-siswa {
            background: #007AFF;
            color: white;
        }

        .bubble-text {
            font-size: 14px;
            color: #303030;
            line-height: 1.5;
            margin: 8px 0;
            word-wrap: break-word;
        }

        .bubble-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #F0F0F0;
        }

        .bubble-date {
            font-size: 11px;
            color: #8696A0;
        }

        .bubble-time {
            font-size: 11px;
            color: #8696A0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Date Divider */
        .date-divider {
            text-align: center;
            margin: 16px 0;
        }

        .date-divider span {
            background: rgba(255,255,255,0.9);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            color: #667781;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #667781;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-state h5 {
            margin-bottom: 8px;
            color: #667781;
        }

        .empty-state p {
            font-size: 14px;
            opacity: 0.7;
        }

        /* Pulse animation for new messages */
        .message-bubble.new-message .bubble-content {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(7, 94, 84, 0.3); }
            70% { box-shadow: 0 0 0 8px rgba(7, 94, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(7, 94, 84, 0); }
        }

        /* Stats Bar */
        .stats-bar {
            background: rgba(7, 94, 84, 0.1);
            padding: 8px 16px;
            font-size: 12px;
            color: #075E54;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="chat-header">
        <a href="dashboard_siswa.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="avatar">
            <i class="fas fa-bell"></i>
        </div>
        <div class="info">
            <h6>Informasi</h6>
            <small><?= count($informasi_list) ?> pesan<?= $informasi_baru > 0 ? ", $informasi_baru baru" : "" ?></small>
        </div>
        <div style="font-size: 20px;">
            <i class="fas fa-ellipsis-v"></i>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="notifikasi.php" class="filter-tab <?= !isset($_GET['filter']) ? 'active' : '' ?>">Semua</a>
        <a href="notifikasi.php?filter=baru" class="filter-tab <?= isset($_GET['filter']) && $_GET['filter'] == 'baru' ? 'active' : '' ?>">Baru</a>
        <a href="notifikasi.php?filter=umum" class="filter-tab <?= isset($_GET['filter']) && $_GET['filter'] == 'umum' ? 'active' : '' ?>">Umum</a>
        <a href="notifikasi.php?filter=siswa" class="filter-tab <?= isset($_GET['filter']) && $_GET['filter'] == 'siswa' ? 'active' : '' ?>">Siswa</a>
    </div>

    <!-- Stats Bar -->
    <?php if ($informasi_baru > 0): ?>
    <div class="stats-bar">
        <i class="fas fa-info-circle"></i> <?= $informasi_baru ?> informasi baru dalam 7 hari terakhir
    </div>
    <?php endif; ?>

    <!-- Chat Container -->
    <div class="chat-container">
        <?php if (count($informasi_list) > 0): ?>
            <?php 
            $filter = $_GET['filter'] ?? '';
            $current_date = '';
            $visible_count = 0;
            
            foreach ($informasi_list as $info): 
                $is_baru = (strtotime($info['tanggal']) > (time() - 7*24*60*60));
                $badge_class = $info['ditujukan'] == 'umum' ? 'badge-umum' : 'badge-siswa';
                $badge_text = $info['ditujukan'] == 'umum' ? 'Umum' : 'Siswa';
                
                // Apply filter
                if ($filter === 'baru' && !$is_baru) continue;
                if ($filter === 'umum' && $info['ditujukan'] !== 'umum') continue;
                if ($filter === 'siswa' && $info['ditujukan'] !== 'siswa') continue;
                
                $visible_count++;
                
                // Date divider
                $message_date = date('d M Y', strtotime($info['tanggal']));
                if ($current_date !== $message_date) {
                    $current_date = $message_date;
                    echo '<div class="date-divider"><span>' . $message_date . '</span></div>';
                }
            ?>
                <div class="message-bubble <?= $is_baru ? 'new-message' : '' ?>">
                    <div class="bubble-content">
                        <div class="bubble-header">
                            <h6 class="bubble-title"><?= htmlspecialchars($info['judul']) ?></h6>
                            <?php if ($is_baru): ?>
                                <span class="bubble-badge badge-new">BARU</span>
                            <?php endif; ?>
                            <span class="bubble-badge <?= $badge_class ?>"><?= $badge_text ?></span>
                        </div>
                        
                        <div class="bubble-text">
                            <?= nl2br(htmlspecialchars($info['isi'])) ?>
                        </div>
                        
                        <div class="bubble-footer">
                            <span class="bubble-date">
                                <i class="fas fa-calendar" style="font-size: 10px;"></i>
                                <?= date('d/m/Y', strtotime($info['tanggal'])) ?>
                            </span>
                            <span class="bubble-time">
                                <?= date('H:i', strtotime($info['tanggal'])) ?>
                                <i class="fas fa-check-double" style="color: #53BDEB;"></i>
                            </span>
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
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h5>Belum Ada Informasi</h5>
                <p>Saat ini belum ada informasi atau pengumuman yang tersedia.</p>
                <small style="display: block; margin-top: 8px;">Informasi baru akan muncul di sini ketika ada pengumuman penting</small>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto scroll to bottom (latest messages)
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.chat-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
            
            // Smooth scroll to new messages
            const newMessages = document.querySelectorAll('.new-message');
            if (newMessages.length > 0) {
                setTimeout(() => {
                    newMessages[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        });

        // Click animation
        document.querySelectorAll('.message-bubble').forEach(bubble => {
            bubble.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html>
