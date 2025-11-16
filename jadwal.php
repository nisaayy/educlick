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

// Helper nama hari
function hariID($hari) {
    $map = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa', 
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    return $map[$hari] ?? $hari;
}

// Ambil semua hari untuk filter
$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Filter hari jika dipilih
$selected_hari = isset($_GET['hari']) ? $_GET['hari'] : '';

// Query jadwal berdasarkan filter
$sql_where = "s.id = ?";
$params = [$siswa_id];
$param_types = "i";

if ($selected_hari && in_array($selected_hari, $hari_list)) {
    $sql_where .= " AND j.hari = ?";
    $params[] = $selected_hari;
    $param_types .= "s";
}

$stmt = $conn->prepare("
    SELECT j.*, m.nama_mapel, g.nama as nama_guru, k.nama_kelas
    FROM jadwal j
    LEFT JOIN mapel m ON j.id_mapel = m.id
    LEFT JOIN guru g ON j.id_guru = g.id
    LEFT JOIN kelas k ON j.id_kelas = k.id
    LEFT JOIN siswa s ON j.id_kelas = s.id_kelas
    WHERE $sql_where
    ORDER BY 
        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        j.jam_mulai
");

// Bind parameters
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$jadwal_result = $stmt->get_result();
$jadwal_data = [];

// Kelompokkan jadwal berdasarkan hari
while ($row = $jadwal_result->fetch_assoc()) {
    $hari = $row['hari'];
    if (!isset($jadwal_data[$hari])) {
        $jadwal_data[$hari] = [];
    }
    $jadwal_data[$hari][] = $row;
}
$stmt->close();

// Hitung total jadwal
$total_jadwal = 0;
foreach ($jadwal_data as $hari_jadwal) {
    $total_jadwal += count($hari_jadwal);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jadwal Pelajaran - SDIT</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Poppins", sans-serif;
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        margin: 0;
        padding: 20px;
        display: flex;
        justify-content: center;
        min-height: 100vh;
    }

    .container {
        width: 100%;
        max-width: 800px;
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    /* Header */
    .header {
        background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
        color: white;
        text-align: center;
        padding: 25px 0;
        position: relative;
    }

    .back-btn {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255,255,255,0.25);
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .back-btn:hover {
        background: rgba(255,255,255,0.4);
        transform: translateY(-50%) scale(1.1);
    }

    .header h3 {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .header p {
        font-size: 15px;
        opacity: 0.95;
        font-weight: 400;
    }

    /* Filter Section */
    .filter-section {
        background: white;
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .filter-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .total-jadwal {
        background: #2196F3;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 1px solid #e0e0e0;
        border-radius: 20px;
        background: white;
        color: #666;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .filter-btn:hover {
        background: #f5f5f5;
        border-color: #2196F3;
    }

    .filter-btn.active {
        background: #2196F3;
        color: white;
        border-color: #2196F3;
    }

    /* Jadwal Container */
    .jadwal-container {
        padding: 20px;
    }

    /* Hari Section */
    .hari-section {
        margin-bottom: 25px;
    }

    .hari-header {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .hari-title {
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hari-count {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .jadwal-list {
        display: grid;
        gap: 12px;
    }

    .jadwal-item {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 18px;
        border-left: 4px solid #2196F3;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .jadwal-item:hover {
        background: #e3f2fd;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
    }

    .jadwal-info {
        flex: 1;
    }

    .jadwal-time {
        font-size: 14px;
        font-weight: 600;
        color: #2196F3;
        margin-bottom: 6px;
        font-family: 'Courier New', monospace;
    }

    .jadwal-mapel {
        font-size: 15px;
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .jadwal-details {
        display: flex;
        gap: 15px;
        font-size: 13px;
        color: #666;
    }

    .jadwal-guru {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .jadwal-kelas {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .jadwal-actions {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e0e0e0;
        color: #666;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .action-btn:hover {
        background: #2196F3;
        color: white;
        border-color: #2196F3;
        transform: scale(1.1);
    }

    /* No Data */
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .no-data i {
        font-size: 64px;
        margin-bottom: 16px;
        display: block;
        opacity: 0.5;
    }

    .no-data p {
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .no-data .subtext {
        font-size: 14px;
        color: #777;
    }

    /* Current Time Indicator */
    .current-time {
        background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 500;
    }

    /* Responsive */
    @media (max-width: 768px) {
        body {
            padding: 15px;
        }
        
        .container {
            margin: 0;
            border-radius: 16px;
        }
        
        .header {
            padding: 20px 0;
        }
        
        .header h3 {
            font-size: 20px;
        }
        
        .filter-section {
            padding: 15px;
        }
        
        .filter-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .filter-buttons {
            width: 100%;
            justify-content: center;
        }
        
        .jadwal-container {
            padding: 15px;
        }
        
        .jadwal-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .jadwal-actions {
            align-self: flex-end;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 10px;
        }
        
        .header {
            padding: 20px 0;
        }
        
        .back-btn {
            width: 35px;
            height: 35px;
        }
        
        .header h3 {
            font-size: 18px;
        }
        
        .header p {
            font-size: 13px;
        }
        
        .filter-buttons {
            gap: 6px;
        }
        
        .filter-btn {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .jadwal-item {
            padding: 15px;
        }
        
        .jadwal-details {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <a href="dashboard_siswa.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h3>Jadwal Pelajaran</h3>
        <p><?= htmlspecialchars($nama_kelas) ?></p>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Hari
            </div>
            <div class="total-jadwal">
                <?= $total_jadwal ?> Jadwal
            </div>
        </div>
        <div class="filter-buttons">
            <a href="jadwal.php" class="filter-btn <?= !$selected_hari ? 'active' : '' ?>">
                Semua Hari
            </a>
            <?php foreach ($hari_list as $hari): ?>
                <a href="jadwal.php?hari=<?= $hari ?>" class="filter-btn <?= $selected_hari == $hari ? 'active' : '' ?>">
                    <?= $hari ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Current Time -->
    <div class="current-time">
        <i class="fas fa-clock"></i>
        <span id="currentDateTime">Loading...</span>
    </div>

    <!-- Jadwal Container -->
    <div class="jadwal-container">
        <?php if ($total_jadwal > 0): ?>
            <?php foreach ($jadwal_data as $hari => $jadwal_hari): ?>
                <div class="hari-section">
                    <div class="hari-header">
                        <div class="hari-title">
                            <i class="fas fa-calendar-day"></i>
                            <?= $hari ?>
                        </div>
                        <div class="hari-count">
                            <?= count($jadwal_hari) ?> Pelajaran
                        </div>
                    </div>
                    
                    <div class="jadwal-list">
                        <?php foreach ($jadwal_hari as $jadwal): ?>
                            <div class="jadwal-item">
                                <div class="jadwal-info">
                                    <div class="jadwal-time">
                                        <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?>
                                    </div>
                                    <div class="jadwal-mapel">
                                        <?= htmlspecialchars($jadwal['nama_mapel']) ?>
                                    </div>
                                    <div class="jadwal-details">
                                        <div class="jadwal-guru">
                                            <i class="fas fa-user-tie"></i>
                                            <?= htmlspecialchars($jadwal['nama_guru'] ?? 'Guru Belum Ditentukan') ?>
                                        </div>
                                        <div class="jadwal-kelas">
                                            <i class="fas fa-door-open"></i>
                                            <?= htmlspecialchars($jadwal['nama_kelas']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="jadwal-actions">
                                    <a href="#" class="action-btn" title="Detail Mapel">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <a href="#" class="action-btn" title="Set Pengingat">
                                        <i class="fas fa-bell"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-calendar-times"></i>
                <p>
                    <?= $selected_hari ? "Tidak ada jadwal untuk hari $selected_hari" : "Belum ada jadwal pelajaran" ?>
                </p>
                <p class="subtext">
                    Silakan hubungi admin jika Anda merasa ini kesalahan
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Update current date and time
    function updateDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('currentDateTime').textContent = 
            now.toLocaleDateString('id-ID', options);
    }

    // Update immediately and every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Highlight current day in filter
    document.addEventListener('DOMContentLoaded', function() {
        const daysMap = {
            'Monday': 'Senin',
            'Tuesday': 'Selasa', 
            'Wednesday': 'Rabu',
            'Thursday': 'Kamis',
            'Friday': 'Jumat',
            'Saturday': 'Sabtu',
            'Sunday': 'Minggu'
        };
        
        const today = daysMap[new Date().toLocaleDateString('en-US', { weekday: 'long' })];
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        filterButtons.forEach(btn => {
            if (btn.textContent.trim() === today) {
                btn.style.fontWeight = '600';
                btn.style.boxShadow = '0 2px 8px rgba(33, 150, 243, 0.3)';
            }
        });
    });

    // Simple reminder functionality
    document.querySelectorAll('.action-btn .fa-bell').forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            const jadwalItem = this.closest('.jadwal-item');
            const mapel = jadwalItem.querySelector('.jadwal-mapel').textContent;
            const waktu = jadwalItem.querySelector('.jadwal-time').textContent;
            
            if (confirm(`Set pengingat untuk:\n${mapel}\n${waktu}?`)) {
                alert('Pengingat berhasil diset!');
                this.style.color = '#ff9800';
            }
        });
    });
</script>

</body>
</html>
